<?php
namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ByteCountResponse;
use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Utils\Types;

/**
 * Response for Read Coils (FC=01)
 */
class ReadCoilsResponse extends ByteCountResponse
{
    /**
     * @var array
     */
    private $coils;

    /**
     * @var int
     */
    private $coilsBytesLength;

    public function __construct($rawData, $unitId = 0, $transactionId = null)
    {
        $data = substr($rawData, 1);
        $this->coilsBytesLength = strlen($data);
        $this->coils = Types::binaryStringToBooleanArray($data);

        parent::__construct($rawData, $unitId, $transactionId);
    }

    public function getFunctionCode()
    {
        return IModbusPacket::READ_COILS;
    }

    public function getCoils()
    {
        return $this->coils;
    }

    public function __toString()
    {
        return parent::__toString()
            . Types::byteArrayToByte(Types::booleanArrayToByteArray($this->coils));
    }

    protected function getLengthInternal()
    {
        return parent::getLengthInternal() + $this->coilsBytesLength;
    }
}