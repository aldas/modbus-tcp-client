<?php

namespace ModbusTcpClient\Packet;


use ModbusTcpClient\ModbusException;
use ModbusTcpClient\Utils\Types;

class Word
{
    /**
     * @var string
     */
    private $data;

    /**
     * @param string $data
     * @throws \ModbusTcpClient\ModbusException
     */
    public function __construct($data)
    {
        $length = strlen($data);
        if ($length === 1) {
            $data = "\x00$data";
        } elseif ($length > 2 || $length === 0) {
            throw new ModbusException("Word can only be constructed from 1 or 2 bytes. Currently $length bytes was given!");
        }
        $this->data = $data;
    }

    /**
     * @return string
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * @return array
     */
    public function getBytes()
    {
        return Types::parseByteArray($this->data);
    }

    /**
     * @return int
     */
    public function getInt16BE()
    {
        return Types::parseInt16BE($this->data);
    }

    /**
     * @return int
     */
    public function getUInt16BE()
    {
        return Types::parseUInt16BE($this->data);
    }

    /**
     * @return int
     */
    public function getLowByteAsInt()
    {
        return Types::parseByte($this->data[1]);
    }

    /**
     * @return int
     */
    public function getHighByteAsInt()
    {
        return Types::parseByte($this->data[0]);
    }

    /**
     * Check if N-th bit is set in data. NB: Bits are counted from 0 and right to left.
     *
     * @param $bit
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isBitSet($bit)
    {
        return Types::isBitSet($this->data, $bit);
    }
}