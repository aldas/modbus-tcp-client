<?php
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
     * @var int
     */
    private $coilCount;

    public function __construct($rawData, $unitId = 0, $transactionId = null)
    {
        parent::__construct($rawData, $unitId, $transactionId);
        $this->coilCount = Types::parseUInt16(substr($rawData, 2, 2));
    }

    public function getFunctionCode()
    {
        return ModbusPacket::WRITE_MULTIPLE_COILS;
    }

    /**
     * @return int
     */
    public function getCoilCount()
    {
        return $this->coilCount;
    }

    protected function getLengthInternal()
    {
        return parent::getLengthInternal() + 2; //coilCount is 2 bytes
    }

    public function __toString()
    {
        return parent::__toString()
            . Types::toInt16($this->coilCount);
    }
}