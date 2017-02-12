<?php
namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Packet\ProtocolDataUnitRequest;
use ModbusTcpClient\Utils\Types;

/**
 * Request for Read Holding Registers (FC=03)
 */
class ReadHoldingRegistersRequest extends ProtocolDataUnitRequest
{
    /**
     * @var int total number of registers (words) requested. Size 2 bytes
     */
    private $quantity;

    public function __construct($startAddress, $quantity, $unitId = 0, $transactionId = null)
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
        throw new \OutOfRangeException("quantity is not set or out of range (0-124): {$this->quantity}");
    }

    public function getFunctionCode()
    {
        return IModbusPacket::READ_HOLDING_REGISTERS;
    }

    public function __toString()
    {
        return parent::__toString()
            . Types::toInt16BE($this->getQuantity());
    }

    /**
     * @return int
     */
    public function getQuantity()
    {
        return $this->quantity;
    }

    protected function getLengthInternal()
    {
        return parent::getLengthInternal() + 2; // quantity size (2 bytes)
    }
}