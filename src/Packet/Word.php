<?php

namespace ModbusTcpClient\Packet;

use ModbusTcpClient\Utils\Types;

/**
 * Word - 2 bytes, 16bits of data
 */
class Word extends AbstractWord
{
    protected function getByteLength(): int
    {
        return 2;
    }

    /**
     * @param int $endianness byte and word order for modbus binary data
     * @return int
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public function getInt16(int $endianness = null): int
    {
        return Types::parseInt16($this->data, $endianness);
    }

    /**
     * @param int $endianness byte and word order for modbus binary data
     * @return int
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public function getUInt16(int $endianness = null): int
    {
        return Types::parseUInt16($this->data, $endianness);
    }

    /**
     * @return int
     */
    public function getLowByteAsInt(): int
    {
        return Types::parseByte($this->data[1]);
    }

    /**
     * @return int
     */
    public function getHighByteAsInt(): int
    {
        return Types::parseByte($this->data[0]);
    }

    /**
     * Combine Words (2x2 bytes) into Double Word (4 bytes). This Word is used as highest bytes and argument $lowWord as lowest bytes
     *
     * @param Word $lowWord
     * @return DoubleWord
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public function combine(Word $lowWord): DoubleWord
    {
        return new DoubleWord($this->getData() . $lowWord->getData());
    }
}