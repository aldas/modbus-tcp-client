<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\StartAddressResponse;
use ModbusTcpClient\Utils\Endian;
use ModbusTcpClient\Utils\Types;

/**
 * Response for Write Multiple Coils (FC=15)
 *
 * Example packet: \x01\x38\x00\x00\x00\x06\x11\x0F\x04\x10\x00\x03
 * \x01\x38 - transaction id
 * \x00\x00 - protocol id
 * \x00\x06 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x11 - unit id
 * \x0F - function code
 * \x04\x10 - start address
 * \x00\x03 - count of coils written
 *
 */
class WriteMultipleCoilsResponse extends StartAddressResponse
{
    /**
     * @var int coils written
     */
    private int $coilCount;

    public function __construct(string $rawData, int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($rawData, $unitId, $transactionId);
        $this->coilCount = Types::parseUInt16(substr($rawData, 2, 2), Endian::BIG_ENDIAN);
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::WRITE_MULTIPLE_COILS;
    }

    /**
     * @return int
     */
    public function getCoilCount(): int
    {
        return $this->coilCount;
    }

    protected function getLengthInternal(): int
    {
        return parent::getLengthInternal() + 2; //coilCount is 2 bytes
    }

    public function __toString(): string
    {
        return parent::__toString()
            . Types::toRegister($this->coilCount);
    }
}
