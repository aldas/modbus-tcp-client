<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Utils\Types;

/**
 * Base class to represent different length modbus words (2/4/8 bytes of raw data)
 */
abstract class AbstractWord
{
    /**
     * @var string
     */
    protected string $data;

    /**
     * @param string $data
     * @throws ModbusException
     */
    public function __construct(string $data)
    {
        $length = strlen($data);
        $wordByteLength = $this->getByteLength();

        if ($length === 0 || $length > $wordByteLength) {
            throw new InvalidArgumentException(static::class . " can only be constructed from 1 to {$this->getByteLength()} bytes. Currently $length bytes was given!");
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
    protected abstract function getByteLength(): int;

    /**
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * @return int[]
     */
    public function getBytes(): array
    {
        return Types::parseByteArray($this->data);
    }

    /**
     * Check if N-th bit is set in data. NB: Bits are counted from 0 and right to left.
     *
     * @param int $bit
     * @return bool
     * @throws \InvalidArgumentException
     */
    public function isBitSet(int $bit): bool
    {
        return Types::isBitSet($this->data, $bit);
    }
}
