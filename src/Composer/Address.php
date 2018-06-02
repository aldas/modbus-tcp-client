<?php

namespace ModbusTcpClient\Composer;


use ModbusTcpClient\Exception\InvalidArgumentException;

abstract class Address
{
    const TYPE_BIT = 'bit';
    const TYPE_BYTE = 'byte';
    const TYPE_INT16 = 'int16';
    const TYPE_UINT16 = 'uint16';
    const TYPE_INT32 = 'int32';
    const TYPE_UINT32 = 'uint32';
    const TYPE_INT64 = 'int64';
    const TYPE_UINT64 = 'uint64';
    const TYPE_FLOAT = 'float';
    const TYPE_STRING = 'string';

    const TYPES = [
        Address::TYPE_BIT,
        Address::TYPE_BYTE,
        Address::TYPE_INT16,
        Address::TYPE_UINT16,
        Address::TYPE_INT32,
        Address::TYPE_UINT32,
        Address::TYPE_UINT64,
        Address::TYPE_INT64,
        Address::TYPE_FLOAT,
        Address::TYPE_STRING,
    ];

    /** @var int */
    protected $address;

    /** @var string */
    protected $type;

    public function __construct(int $address, string $type)
    {
        $this->address = $address;
        $this->type = $type;

        if (!in_array($type, $this->getAllowedTypes(), true)) {
            throw new InvalidArgumentException("Invalid address type given! type: '{$type}', address: {$address}");
        }
    }

    abstract protected function getAllowedTypes(): array;

    public function getSize(): int
    {
        $size = 1;
        switch ($this->type) {
            case self::TYPE_INT32:
            case self::TYPE_UINT32:
            case self::TYPE_FLOAT:
                $size = 2;
                break;
            case self::TYPE_INT64:
            case self::TYPE_UINT64:
                $size = 4;
                break;
        }
        return $size;
    }

    public function getAddress(): int
    {
        return $this->address;
    }

    public function getType(): string
    {
        return $this->type;
    }
}