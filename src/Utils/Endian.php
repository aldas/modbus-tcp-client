<?php

namespace ModbusTcpClient\Utils;

/**
 * Data types with Double Word (4 bytes) length can have different byte order when sent over wire depending of PLC vendor
 * For some data is sent in true big endian format, Big-Endian with Low Word first. This class is to provide flags
 * to switch needed byte order when parsing data.
 *
 * Background info: http://www.digi.com/wiki/developer/index.php/Modbus_Floating_Points (about floats but 32bit int is also double word)
 *
 *
 * Example:
 * 32bit (4 byte) integer 67305985 is in hex 0x01020304 (little endian), most significant byte is 01 and the
 * lowest byte contain hex value 04.
 * Source: http://unixpapa.com/incnote/byteorder.html
 *
 * 32bit (dword) integer is in:
 *      Little Endian (ABCD) = 0x01020304  (0x04 + (0x03 << 8) + (0x02 << 16) + (0x01 << 24))
 *
 * May be sent over tcp/udp as:
 *      Big Endian (DCBA) = 0x04030201
 *      Big Endian Low Word First (BADC) = 0x02010403 <-- used by WAGO 750-XXX to send modbus packets over tcp/udp
 *
 */
class Endian
{
    const BIG_ENDIAN = 1;
    const LITTLE_ENDIAN = 2;

    /**
     * Double words (32bit types) consist of two 16bit words. Different PLCs send double words differently over wire
     * So 0xDCBA can be sent low word (0xBA) first 0xBADC or high word (0xDC) first 0xDCBA. High word first on true big/little endian
     * and does not have separate flag
     */
    const LOW_WORD_FIRST = 4;

    /**
     * Used by WAGO 750-XXX as endianness.
     *
     * When bytes for little endian are in 'ABCD' order then Big Endian Low Word First is in 'BADC' order
     * This mean that high word (BA) is first and low word (DC) for double word is last and bytes in words are in big endian order.
     */
    const BIG_ENDIAN_LOW_WORD_FIRST = self::BIG_ENDIAN | self::LOW_WORD_FIRST;

    public static $defaultEndian = self::BIG_ENDIAN_LOW_WORD_FIRST;

    public static function getCurrentEndianness(int $endianness = null): int
    {
        return $endianness === null ? static::$defaultEndian : $endianness;
    }
}