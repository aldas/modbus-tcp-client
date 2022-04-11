<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet;

use ModbusTcpClient\Exception\ModbusException;
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
     * @param int|null $endianness byte and word order for modbus binary data
     * @return int
     */
    public function getUInt64(int $endianness = null): int
    {
        return Types::parseUInt64($this->getData(), $endianness);
    }

    /**
     * @param int|null $endianness byte and word order for modbus binary data
     * @return int
     */
    public function getInt64(int $endianness = null): int
    {
        return Types::parseInt64($this->getData(), $endianness);
    }

    /**
     * @param int|null $endianness byte and word order for modbus binary data
     * @return float
     */
    public function getDouble(int $endianness = null): float
    {
        return Types::parseDouble($this->getData(), $endianness);
    }

    /**
     * @return DoubleWord
     * @throws ModbusException
     */
    public function getLowBytesAsDoubleWord(): DoubleWord
    {
        return new DoubleWord(substr($this->getData(), 4));
    }

    /**
     * @return DoubleWord
     * @throws ModbusException
     */
    public function getHighBytesAsDoubleWord(): DoubleWord
    {
        return new DoubleWord(substr($this->getData(), 0, 4));
    }

    /**
     * Create Quad Word of 4 words. word1 is highest bytes amd word4 lowest bytes
     *
     * @param Word $word1
     * @param Word $word2
     * @param Word $word3
     * @param Word $word4
     * @return QuadWord
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
