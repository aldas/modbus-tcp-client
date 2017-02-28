<?php
namespace ModbusTcpClient\Packet\ModbusFunction;


use ModbusTcpClient\ModbusException;
use ModbusTcpClient\Packet\ByteCountResponse;
use ModbusTcpClient\Packet\DoubleWord;
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
     * Return data as splitted into words. Each word contains 2 bytes
     *
     * @return Word[] array of Words. each arrays cointains 2 bytes
     * @throws \ModbusTcpClient\ModbusException
     */
    public function getWords()
    {
        if ($this->getByteCount() % 2 !== 0) {
            throw new ModbusException('getWords needs packet byte count to be multiple of 2');
        }
        $words = [];
        foreach (str_split($this->data, 2) as $str) {
            $words[] = new Word($str);
        }
        return $words;
    }

    /**
     * Return data as splitted into double words. Each dword contains 4 bytes
     *
     * @return DoubleWord[] array of Double Words. each arrays cointains 4 bytes
     * @throws \ModbusTcpClient\ModbusException
     */
    public function getDoubleWords()
    {
        $byteCount = $this->getByteCount();
        if ($byteCount % 4 !== 0) {
            throw new ModbusException('getDoubleWords needs packet byte count to be multiple of 4');
        }

        $words = [];
        foreach (str_split($this->data, 4) as $str) {
            $words[] = new DoubleWord($str);
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
