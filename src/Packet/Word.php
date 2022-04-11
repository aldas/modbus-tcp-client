<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet;

use ModbusTcpClient\Exception\ModbusException;
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
     * @param int|null $endianness byte and word order for modbus binary data
     * @return int
     */
    public function getInt16(int $endianness = null): int
    {
        return Types::parseInt16($this->data, $endianness);
    }

    /**
     * @param int|null $endianness byte and word order for modbus binary data
     * @return int
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
     * @throws ModbusException
     */
    public function combine(Word $lowWord): DoubleWord
    {
        return new DoubleWord($this->getData() . $lowWord->getData());
    }
}
