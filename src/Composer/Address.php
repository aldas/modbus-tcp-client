<?php

namespace ModbusTcpClient\Composer;


interface Address
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

    public function getSize(): int;

    public function getAddress(): int;
}
