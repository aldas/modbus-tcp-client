<?php

declare(strict_types=1);

namespace ModbusTcpClient\Composer;

interface Address
{
    public const TYPE_BIT = 'bit';
    public const TYPE_BYTE = 'byte';
    public const TYPE_INT16 = 'int16';
    public const TYPE_UINT16 = 'uint16';
    public const TYPE_INT32 = 'int32';
    public const TYPE_UINT32 = 'uint32';
    public const TYPE_INT64 = 'int64';
    public const TYPE_UINT64 = 'uint64';
    public const TYPE_FLOAT = 'float';
    public const TYPE_DOUBLE = 'double';
    public const TYPE_STRING = 'string';

    public const TYPES = [
        Address::TYPE_BIT,
        Address::TYPE_BYTE,
        Address::TYPE_INT16,
        Address::TYPE_UINT16,
        Address::TYPE_INT32,
        Address::TYPE_UINT32,
        Address::TYPE_UINT64,
        Address::TYPE_INT64,
        Address::TYPE_FLOAT,
        Address::TYPE_DOUBLE,
        Address::TYPE_STRING,
    ];

    public function getSize(): int;

    public function getAddress(): int;

    public function getType(): string;
}
