<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\StartAddressResponse;
use ModbusTcpClient\Utils\Endian;
use ModbusTcpClient\Utils\Types;

/**
 * Response for Write Multiple Registers (FC=16)
 *
 * Example packet: \x01\x38\x00\x00\x00\x06\x11\x10\x04\x10\x00\x03
 * \x01\x38 - transaction id
 * \x00\x00 - protocol id
 * \x00\x06 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x11 - unit id
 * \x10 - function code
 * \x04\x10 - start address
 * \x00\x03 - count of registers written
 *
 */
class WriteMultipleRegistersResponse extends StartAddressResponse
{
    /**
     * @var int number of registers written
     */
    private int $registersCount;

    public function __construct(string $rawData, int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($rawData, $unitId, $transactionId);
        $this->registersCount = Types::parseUInt16(substr($rawData, 2, 2), Endian::BIG_ENDIAN);
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::WRITE_MULTIPLE_REGISTERS;
    }

    /**
     * @return int
     */
    public function getRegistersCount(): int
    {
        return $this->registersCount;
    }

    protected function getLengthInternal(): int
    {
        return parent::getLengthInternal() + 2; //registersCount is 2 bytes
    }

    public function __toString(): string
    {
        return parent::__toString()
            . Types::toRegister($this->registersCount);
    }
}
