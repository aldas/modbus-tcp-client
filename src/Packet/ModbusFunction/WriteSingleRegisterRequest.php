<?php
namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Packet\ProtocolDataUnitRequest;
use ModbusTcpClient\Utils\Types;


/**
 * Request for Write Single Register (FC=06)
 */
class WriteSingleRegisterRequest extends ProtocolDataUnitRequest
{

    /**
     * @var int value to be sent to modbus
     */
    private $value;

    public function __construct($startAddress, $value, $unitId = 0, $transactionId = null)
    {
        parent::__construct($startAddress, $unitId, $transactionId);
        $this->value = $value;

        $this->validate();
    }

    public function getFunctionCode()
    {
        return IModbusPacket::WRITE_SINGLE_REGISTER;
    }

    public function getLength()
    {
        return parent::getLength() + 2; // value size (2 bytes)
    }

    public function __toString()
    {
        return parent::__toString()
            . Types::toUInt16BE($this->getValue());
    }

    public function validate()
    {
        parent::validate();
        if ((null !== $this->value) && (($this->value >= Types::MIN_VALUE_INT16) && ($this->value <= Types::MAX_VALUE_INT16))) {
            return;
        }
        throw new \OutOfRangeException("value is not set or out of range (int16): {$this->value}");
    }

    public static function parse($binaryString)
    {
        // TODO: Implement parse() method.
    }

    /**
     * @return int
     */
    public function getValue()
    {
        return $this->value;
    }
}