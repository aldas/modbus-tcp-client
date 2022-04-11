<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet;

use ModbusTcpClient\Exception\ModbusException;
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
     * @param int|null $endianness byte and word order for modbus binary data
     *
     * NB: On 32bit php and having highest bit set method will return float instead of int value. This is due 32bit php supports only 32bit signed integers
     *
     * @return int|float
     */
    public function getUInt32(int $endianness = null): int|float
    {
        return Types::parseUInt32($this->getData(), $endianness);
    }

    /**
     * @param int|null $endianness byte and word order for modbus binary data
     * @return int
     */
    public function getInt32(int $endianness = null): int
    {
        return Types::parseInt32($this->getData(), $endianness);
    }

    /**
     * @param int|null $endianness byte and word order for modbus binary data
     * @return float
     */
    public function getFloat(int $endianness = null): float
    {
        return Types::parseFloat($this->getData(), $endianness);
    }

    /**
     * @return Word
     * @throws ModbusException
     */
    public function getLowBytesAsWord(): Word
    {
        return new Word(substr($this->getData(), 2));
    }

    /**
     * @return Word
     * @throws ModbusException
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
     * @throws ModbusException
     */
    public function combine(DoubleWord $lowDoubleWord): QuadWord
    {
        return new QuadWord($this->getData() . $lowDoubleWord->getData());
    }
}
