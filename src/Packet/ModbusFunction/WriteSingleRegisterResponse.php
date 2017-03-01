<?php
namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Packet\StartAddressResponse;
use ModbusTcpClient\Utils\Types;

/**
 * Response for Write Single Register (FC=06)
 */
class WriteSingleRegisterResponse extends StartAddressResponse
{
    /**
     * @var int
     */
    private $value;

    public function __construct($rawData, $unitId = 0, $transactionId = null)
    {
        parent::__construct($rawData, $unitId, $transactionId);
        $this->value = Types::parseUInt16(substr($rawData, 2, 2));
    }

    public function getFunctionCode()
    {
        return IModbusPacket::WRITE_SINGLE_REGISTER;
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }

    protected function getLengthInternal()
    {
        return parent::getLengthInternal() + 2; //register is 2 bytes
    }

    public function __toString()
    {
        return parent::__toString()
            . Types::toInt16($this->value);
    }
}