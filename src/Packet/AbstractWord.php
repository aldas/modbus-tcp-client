<?php

namespace ModbusTcpClient\Packet;

use ModbusTcpClient\ModbusException;
use ModbusTcpClient\Utils\Types;

/**
 * Base class to represent different length modbus words (2/4/8 bytes of raw data)
 */
abstract class AbstractWord
{
    /**
     * @var string
     */
    protected $data;

    /**
     * @param string $data
     * @throws \ModbusTcpClient\ModbusException
     */
    public function __construct($data)
    {
        $length = strlen($data);
        $wordByteLength = $this->getByteLength();

        if ($length === 0 || $length > $wordByteLength) {
            throw new ModbusException(static::class . " can only be constructed from 1 to {$this->getByteLength()} bytes. Currently $length bytes was given!");
        } elseif ($length < $wordByteLength) {
            $data = str_pad($data, $wordByteLength, "\x00", STR_PAD_LEFT);
        }
        $this->data = $data;
    }

    /**
     * Number of bytes contained in word
     *
     * @return int
     */
    protected abstract function getByteLength();

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