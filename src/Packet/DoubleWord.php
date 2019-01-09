<?php

namespace ModbusTcpClient\Packet;

use ModbusTcpClient\Utils\Types;

/**
 * Double word - 4 bytes, 32bits of data
 */
class DoubleWord extends AbstractWord
{
    protected function getByteLength(): int
    {
        return 4;
    }

    /**
     * @param int $endianness byte and word order for modbus binary data
     *
     * NB: On 32bit php and having highest bit set method will return float instead of int value. This is due 32bit php supports only 32bit signed integers
     *
     * @return int|float
     * @throws \RuntimeException
     */
    public function getUInt32(int $endianness = null)
    {
        return Types::parseUInt32($this->getData(), $endianness);
    }

    /**
     * @param int $endianness byte and word order for modbus binary data
     * @return int
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public function getInt32(int $endianness = null): int
    {
        return Types::parseInt32($this->getData(), $endianness);
    }

    /**
     * @param int $endianness byte and word order for modbus binary data
     * @return float
     * @throws \RuntimeException
     */
    public function getFloat(int $endianness = null): float
    {
        return Types::parseFloat($this->getData(), $endianness);
    }

    /**
     * @return Word
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public function getLowBytesAsWord(): Word
    {
        return new Word(substr($this->getData(), 2));
    }

    /**
     * @return Word
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public function getHighBytesAsWord(): Word
    {
        return new Word(substr($this->getData(), 0, 2));
    }

    /**
     * Combine DoubleWords (2x(2x2) bytes) into Quad Word (8 bytes). This Double Word is used as highest bytes and argument $lowDoubleWord as lowest bytes
     *
     * @param DoubleWord $lowDoubleWord
     * @return QuadWord
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public function combine(DoubleWord $lowDoubleWord): QuadWord
    {
        return new QuadWord($this->getData() . $lowDoubleWord->getData());
    }
}