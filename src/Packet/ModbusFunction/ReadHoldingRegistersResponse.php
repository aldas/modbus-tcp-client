<?php
namespace ModbusTcpClient\Packet\ModbusFunction;


use ModbusTcpClient\Packet\ByteCountResponse;
use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Packet\Word;
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
        $this->data = substr($rawData, 1); //first byte is byteCount. remove it
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
        return Types::parseByteArray($this->data);
    }

    /**
     * Return data as splitted into chunks. Each chunk contains 2 elements
     *
     * @return Word[] array of Words. each arrays cointain 2 elements (bytes)
     * @throws \ModbusTcpClient\ModbusException
     */
    public function getWords()
    {
        $words = [];
        foreach (str_split($this->data, 2) as $str) {
            $words[] = new Word($str);
        }
        return $words;
    }

    public function __toString()
    {
        return parent::__toString()
            . $this->data;
    }

    protected function getLengthInternal()
    {
        return parent::getLengthInternal() + $this->getByteCount();
    }
}
