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
 * Request for Read Coils (FC=01)
 *
 * Example packet: \x81\x80\x00\x00\x00\x06\x10\x01\x00\x6B\x00\x03
 * \x81\x80 - transaction id
 * \x00\x00 - protocol id
 * \x00\x06 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x10 - unit id
 * \x01 - function code
 * \x00\x6B - start address
 * \x00\x03 - coils quantity to return
 *
 */
class ReadCoilsRequest extends ProtocolDataUnitRequest implements ModbusRequest
{
    /**
     * @var int total number of coils requested. Size 2 bytes
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

        if (($this->quantity > 0 && $this->quantity <= 2048)) {
            // 2048 coils is due that in response data size field is 1 byte so max 256*8=2048 coils can be returned
            return;
        }
        throw new InvalidArgumentException("quantity is not set or out of range (1-2048): {$this->quantity}", 3);
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::READ_COILS;
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
     * Parses binary string to ReadCoilsRequest or return ErrorResponse on failure
     *
     * @param string $binaryString
     * @return ReadCoilsRequest|ErrorResponse
     */
    public static function parse(string $binaryString): ErrorResponse|ReadCoilsRequest
    {
        return self::parseStartAddressPacket(
            $binaryString,
            12,
            ModbusPacket::READ_COILS,
            function (int $transactionId, int $unitId, int $startAddress) use ($binaryString) {
                $quantity = Types::parseUInt16($binaryString[10] . $binaryString[11], Endian::BIG_ENDIAN);
                return new self($startAddress, $quantity, $unitId, $transactionId);
            }
        );
    }
}
