<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\StartAddressResponse;
use ModbusTcpClient\Utils\Types;

/**
 * Response for Write Multiple Coils (FC=15)
 */
class WriteMultipleCoilsResponse extends StartAddressResponse
{
    /**
     * @var int coils written
     */
    private $coilCount;

    public function __construct(string $rawData, int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($rawData, $unitId, $transactionId);
        $this->coilCount = Types::parseUInt16(substr($rawData, 2, 2));
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