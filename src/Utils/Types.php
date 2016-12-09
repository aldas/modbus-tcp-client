<?php

namespace ModbusTcpClient\Utils;


class Types
{
    const MAX_VALUE_UINT16 = 0xFFFF;
    const MAX_VALUE_BYTE = 0xFF;

    public static function toUInt16BE($data)
    {
        return pack('n', $data);
    }

    public static function toByte($data) {
        return pack('C', $data);
    }

}