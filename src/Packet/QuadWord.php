<?php

namespace ModbusTcpClient\Packet;

use ModbusTcpClient\Utils\Types;

/**
 * Quad word - 8 bytes, 64bits of data
 */
class QuadWord extends AbstractWord
{
    protected function getByteLength()
    {
        return 8;
    }

    /**
     * @param int $endianness byte and word order for modbus binary data
     * @return int
     */
    public function getUInt64($endianness = null)
    {
        return Types::parseUInt64($this->getData(), $endianness);
    }

    /**
     * @return DoubleWord
     * @throws \ModbusTcpClient\ModbusException
     */
    public function getLowBytesAsDoubleWord()
    {
        return new DoubleWord(substr($this->getData(), 4));
    }

    /**
     * @return DoubleWord
     * @throws \ModbusTcpClient\ModbusException
     */
    public function getHighBytesAsDoubleWord()
    {
        return new DoubleWord(substr($this->getData(), 0, 4));
    }
}