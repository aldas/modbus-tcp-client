<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusRequest;
use ModbusTcpClient\Packet\ProtocolDataUnitRequest;
use ModbusTcpClient\Utils\Endian;
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
    private int $quantity;

    public function __construct(int $startAddress, int $quantity, int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($startAddress, $unitId, $transactionId);

        $this->quantity = $quantity;

        $this->validate();

    }

    public function validate(): void
    {
        parent::validate();
        if (($this->quantity > 0 && $this->quantity <= 124)) {
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
     * @param string $binaryString
     * @return ReadHoldingRegistersRequest|ErrorResponse
     */
    public static function parse(string $binaryString): ErrorResponse|ReadHoldingRegistersRequest
    {
        return self::parseStartAddressPacket(
            $binaryString,
            12,
            ModbusPacket::READ_HOLDING_REGISTERS,
            function (int $transactionId, int $unitId, int $startAddress) use ($binaryString) {
                $quantity = Types::parseUInt16($binaryString[10] . $binaryString[11], Endian::BIG_ENDIAN);
                return new self($startAddress, $quantity, $unitId, $transactionId);
            }
        );
    }
}
