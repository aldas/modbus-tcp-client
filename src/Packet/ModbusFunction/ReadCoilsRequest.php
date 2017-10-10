<?php
namespace ModbusTcpClient\Packet\ModbusFunction;


use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ProtocolDataUnitRequest;
use ModbusTcpClient\Utils\Types;

/**
 * Request for Read Coils (FC=01)
 */
class ReadCoilsRequest extends ProtocolDataUnitRequest
{
    /**
     * @var int total number of coils requested. Size 2 bytes
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

        if ((null !== $this->quantity) && ($this->quantity > 0 && $this->quantity <= Types::MAX_VALUE_UINT16)) {
            return;
        }
        throw new \OutOfRangeException("quantity is not set or out of range (1-65535): {$this->quantity}");
    }

    public function getFunctionCode()
    {
        return ModbusPacket::READ_COILS;
    }

    public function __toString()
    {
        return parent::__toString()
            . Types::toInt16($this->getQuantity());
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