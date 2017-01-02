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
        parent::__construct($startAddress, $unitId, $transactionId);
        $this->coils = $coils;
        $this->coilCount = count($this->coils);
        $this->coilBytesSize = ($this->coilCount + 7) / 8;

        $this->validate();
    }

    public function getFunctionCode()
    {
        return IModbusPacket::WRITE_MULTIPLE_COILS;
    }

    public function getLength()
    {
        return parent::getLength() + (1 + $this->coilBytesSize); // coil count + number of bytes coils need for data
    }

    public function __toString()
    {
        return parent::__toString()
            . Types::toByte($this->coilCount)
            . Types::byteArrayToByte(Types::booleanArrayToByteArray($this->coils));
    }

    public function validate()
    {
        parent::validate();
        if (null === $this->coils) {
            throw new \OutOfRangeException('coils is not set');
        }
        if ($this->coilCount === 0 || $this->coilCount > 2048) {
            throw new \OutOfRangeException("coils count out of range (1-2048): {$this->coilCount}");
        }
    }

    public static function parse($binaryString)
    {
        // TODO: Implement parse() method.
    }

    /**
     * @return array
     */
    public function getCoils()
    {
        return $this->coils;
    }
}