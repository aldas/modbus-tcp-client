<?php
namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Packet\ProtocolDataUnitRequest;
use ModbusTcpClient\Utils\Types;

/**
 * Request for Write Multiple Coils (FC=15)
 */
class WriteMultipleCoilsRequest extends ProtocolDataUnitRequest
{
    /**
     * @var array coils (array of booleans)
     */
    private $coils;
    private $coilCount;
    private $coilBytesSize;

    public function __construct($startAddress, array $coils, $unitId = 0, $transactionId = null)
    {
        $this->coils = $coils;
        $this->coilCount = count($this->coils);
        $this->coilBytesSize = (int)(($this->coilCount + 7) / 8);

        parent::__construct($startAddress, $unitId, $transactionId);

        $this->validate();
    }

    public function validate()
    {
        parent::validate();

        if ($this->coilCount === 0 || $this->coilCount > 2048) {
            throw new \OutOfRangeException("coils count out of range (1-2048): {$this->coilCount}");
        }
    }

    public function getFunctionCode()
    {
        return IModbusPacket::WRITE_MULTIPLE_COILS;
    }

    public function __toString()
    {
        return parent::__toString()
            . Types::toInt16BE($this->coilCount)
            . Types::toByte($this->coilBytesSize)
            . Types::byteArrayToByte(Types::booleanArrayToByteArray($this->coils));
    }

    /**
     * @return array
     */
    public function getCoils()
    {
        return $this->coils;
    }

    protected function getLengthInternal()
    {
        return parent::getLengthInternal() + (3 + $this->coilBytesSize); // coilCount + coilBytesSize + number of bytes coils need for data
    }
}