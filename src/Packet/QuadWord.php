<?php

namespace ModbusTcpClient\Packet;

use ModbusTcpClient\Utils\Types;

/**
 * Quad word - 8 bytes, 64bits of data
 */
class QuadWord extends AbstractWord
{
    protected function getByteLength(): int
    {
        return 8;
    }

    /**
     * @param int $endianness byte and word order for modbus binary data
     * @return int
     */
    public function getUInt64(int $endianness = null): int
    {
        return Types::parseUInt64($this->getData(), $endianness);
    }

    /**
     * @param int $endianness byte and word order for modbus binary data
     * @return int
     */
    public function getInt64(int $endianness = null): int
    {
        return Types::parseInt64($this->getData(), $endianness);
    }

    /**
     * @return DoubleWord
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public function getLowBytesAsDoubleWord(): DoubleWord
    {
        return new DoubleWord(substr($this->getData(), 4));
    }

    /**
     * @return DoubleWord
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public function getHighBytesAsDoubleWord(): DoubleWord
    {
        return new DoubleWord(substr($this->getData(), 0, 4));
    }

    /**
     * Create Quad Word of 4 words. word1 is highest bytes amd word4 lowest bytes
     *
     * @return QuadWord
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public static function fromWords(Word $word1, Word $word2, Word $word3, Word $word4): QuadWord
    {
        return new QuadWord(
            $word1->getData() .
            $word2->getData() .
            $word3->getData() .
            $word4->getData()
        );
    }
}