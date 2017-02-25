<?php

namespace ModbusTcpClient\Packet;

use ModbusTcpClient\Utils\Types;

class DoubleWord extends AbstractWord
{
    protected function getByteLength()
    {
        return 4;
    }

    /**
     * @return int
     * @throws \RuntimeException
     */
    public function getUInt32()
    {
        return Types::parseUInt32BE($this->getData());
    }

    /**
     * @return int
     */
    public function getInt32()
    {
        return Types::parseInt32BE($this->getData());
    }

    /**
     * @return float
     * @throws \RuntimeException
     */
    public function getFloat()
    {
        return Types::parseFloat($this->getData());
    }

    /**
     * @return Word
     * @throws \ModbusTcpClient\ModbusException
     */
    public function getLowBytesAsWord()
    {
        return new Word(substr($this->getData(), 2));
    }

    /**
     * @return Word
     * @throws \ModbusTcpClient\ModbusException
     */
    public function getHighBytesAsWord()
    {
        return new Word(substr($this->getData(), 0, 2));
    }
}