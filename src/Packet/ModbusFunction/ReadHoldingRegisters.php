<?php

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusApplicationHeader;
use ModbusTcpClient\Packet\ProtocolDataUnit;
use ModbusTcpClient\Utils\Types;

/**
 * Read Holding Registers (FC=03)
 */
class ReadHoldingRegisters extends ProtocolDataUnit
{
    const FUNCTION_CODE = 3;

    private $quantity;

    /**
     * @var ModbusApplicationHeader
     */
    private $header;

    public function __construct($startAddress, $quantity, $unitId = 0, $transactionId = null)
    {
        parent::__construct($startAddress);
        $this->quantity = $quantity;

        $this->validate();

        $this->header = new ModbusApplicationHeader($this->getLength(), $unitId, $transactionId);
    }

    public function getLength()
    {
        return parent::getLength() + 3; // function_code size (1) + quantity size (2)
    }

    /**
     * @return mixed
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    /**
     * @return ModbusApplicationHeader
     */
    public function getHeader()
    {
        return $this->header;
    }

    public function getFunctionCode()
    {
        return self::FUNCTION_CODE;
    }

    public function __toString()
    {
        return b''
            . $this->header
            . Types::toByte($this->getFunctionCode())
            . Types::toUInt16BE($this->getStartAddress())
            . Types::toUInt16BE($this->getQuantity());
    }

    public function validate()
    {
        parent::validate();
        if ((null !== $this->quantity) && ($this->quantity > 0 || $this->quantity <= Types::MAX_VALUE_UINT16)) {
            return true;
        }
        throw new \OutOfRangeException("quantity is not set or out of range: {$this->quantity}");
    }

    public static function parse($binaryString)
    {
//        $data = unpack();
    }
}