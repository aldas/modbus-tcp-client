<?php

namespace ModbusTcpClient\Utils;


class Types
{
    const MAX_VALUE_UINT16 = 0xFFFF;
    const MIN_VALUE_UINT16 = 0x0;

    const MAX_VALUE_INT16 = 0x7FFF;
    const MIN_VALUE_INT16 = -32768; // 0x8000 as hex

    const MAX_VALUE_BYTE = 0xFF;
    const MIN_VALUE_BYTE = 0x0;

    public static function toUInt16BE($data)
    {
        return pack('n', $data);
    }

    public static function toInt32BE($data)
    {
        return pack('N', $data);
    }

    public static function parseUInt16BE($binaryData)
    {
        return unpack('n', $binaryData)[1];
    }

    public static function toByte($data)
    {
        return pack('C', $data);
    }

    public static function byteArrayToByte(array $data)
    {
        return pack('C*', ...$data);
    }

    public static function parseByte($data)
    {
        return unpack('C', $data)[1];
    }

    public static function booleanArrayToByteArray(array $booleans)
    {
        $result = [];
        $count = count($booleans);

        $currentByte = 0;
        for ($index = 0; $index < $count; $index++) {
            $bit = $index % 8;
            if ($index !== 0 && $bit === 0) {
                $result[] = $currentByte;
                $currentByte = 0;
            }

            $current = $booleans[$index];
            if ($current) {
                $currentByte |= 1 << $bit;
            }
        }
        $result[] = $currentByte;

        return $result;
    }

    public static function int16ArrayToByteArray(array $ints)
    {
        return array_map(function ($elem) {
            if ($elem) {
                return Types::toUInt16BE($elem);
            }
            return null;
        }, $ints);
    }

    public static function binaryStringToBooleanArray($binary)
    {
        $result = [];
        $coilCount = 8 * strlen($binary);

        for ($index = 0; $index < $coilCount; $index++) {
            $bit = $index % 8;
            if ($bit === 0) {
                $byteAsInt = ord($binary[(int)($index / 8)]);
            }
            $result[] = (($byteAsInt & (1 << $bit)) >> $bit) === 1;

        }
        return $result; //TODO refactor to generator?
    }

}