<?php
namespace ModbusTcpClient\Packet\ModbusFunction;


use ModbusTcpClient\Packet\ByteCountResponse;
use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Utils\Types;

/**
 * Response for Read Holding Registers (FC=03)
 */
class ReadHoldingRegistersResponse extends ByteCountResponse
{
    /**
     * @var array
     */
    private $data;

    public function __construct($rawData, $unitId = 0, $transactionId = null)
    {
        parent::__construct($rawData, $unitId, $transactionId);

        $binaryData = substr($rawData, 1);
        $this->data = array_values(unpack('C*', $binaryData));
    }

    public function getFunctionCode()
    {
        return IModbusPacket::READ_HOLDING_REGISTERS;
    }

    /**
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Return data as splitted into chunks. Each chunk contains 2 elements
     *
     * @return array array of arrays. each arrays cointain 2 elements (bytes)
     */
    public function getWords()
    {
        return array_chunk($this->data, 2);
    }

    public function __toString()
    {
        return parent::__toString()
            . Types::byteArrayToByte($this->data);
    }

    protected function getLengthInternal()
    {
        return parent::getLengthInternal() + $this->getByteCount();
    }
}
