<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusApplicationHeader;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusRequest;
use ModbusTcpClient\Packet\ProtocolDataUnitRequest;
use ModbusTcpClient\Utils\Types;

/**
 * Request for Read Holding Registers (FC=03)
 *
 * Example packet: \x00\x01\x00\x00\x00\x06\x01\x03\x00\x6B\x00\x01
 * \x00\x01 - transaction id
 * \x00\x00 - protocol id
 * \x00\x06 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x01 - unit id
 * \x03 - function code
 * \x00\x6B - start address
 * \x00\x01 - holding registers quantity to return
 *
 */
class ReadHoldingRegistersRequest extends ProtocolDataUnitRequest implements ModbusRequest
{
    /**
     * @var int total number of registers (words) requested. Size 2 bytes
     */
    private $quantity;

    public function __construct(int $startAddress, int $quantity, int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($startAddress, $unitId, $transactionId);

        $this->quantity = $quantity;

        $this->validate();

    }

    public function validate()
    {
        parent::validate();
        if ((null !== $this->quantity) && ($this->quantity > 0 && $this->quantity <= 124)) {
            return;
        }
        throw new InvalidArgumentException("quantity is not set or out of range (0-124): {$this->quantity}", 3);
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::READ_HOLDING_REGISTERS;
    }

    public function __toString(): string
    {
        return parent::__toString()
            . Types::toRegister($this->getQuantity());
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    protected function getLengthInternal(): int
    {
        return parent::getLengthInternal() + 2; // quantity size (2 bytes)
    }

    /**
     * Parses binary string to ReadHoldingRegistersRequest or return ErrorResponse on failure
     *
     * @param $binaryString
     * @return ReadHoldingRegistersRequest|ErrorResponse
     */
    public static function parse($binaryString)
    {
        if ($binaryString === null || strlen($binaryString) !== 12) {
            return new ErrorResponse(new ModbusApplicationHeader(2, 0, 0),
                ModbusPacket::READ_HOLDING_REGISTERS,
                4 // Server failure
            );
        }

        $transactionId = Types::parseUInt16($binaryString[0] . $binaryString[1]);
        $unitId = Types::parseByte($binaryString[6]);
        if (ModbusPacket::READ_HOLDING_REGISTERS !== ord($binaryString[7])) {
            return new ErrorResponse(
                new ModbusApplicationHeader(2, $unitId, $transactionId),
                ModbusPacket::READ_HOLDING_REGISTERS,
                1 // Illegal function
            );
        }

        $startAddress = Types::parseUInt16($binaryString[8] . $binaryString[9]);
        $quantity = Types::parseUInt16($binaryString[10] . $binaryString[11]);
        try {
            return new ReadHoldingRegistersRequest($startAddress, $quantity, $unitId, $transactionId);
        } catch (\Exception $exception) {
            // constructor does validation and throws exception so not to mix returning errors and throwing exceptions
            // we catch exception here and return it as a error response.
            $errorCode = $exception instanceof InvalidArgumentException ? $exception->getCode() : 3; // Illegal data value
            return new ErrorResponse(
                new ModbusApplicationHeader(2, $unitId, $transactionId),
                ModbusPacket::READ_HOLDING_REGISTERS,
                $errorCode
            );
        }
    }
}
