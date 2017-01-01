<?php

namespace ModbusTcpClient\Utils;


class Types
{
    const MAX_VALUE_UINT16 = 0xFFFF;
    const MIN_VALUE_UINT16 = 0x0;

    const MAX_VALUE_INT16 = 0x7FFF;
    const MIN_VALUE_INT16 = -32768; // 0x8000 as hex

    const MAX_VALUE_BYTE = 0xFF;

    public static function toUInt16BE($data)
    {
        return pack('n', $data);
    }

    public static function parseUInt16BE($binaryData)
    {
        return unpack('n', $binaryData)[1];
    }

    public static function toByte($data) {
        return pack('C', $data);
    }

    public static function parseByte($data) {
        return unpack('C', $data)[1];
    }

}