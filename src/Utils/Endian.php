<?php
declare(strict_types=1);

namespace ModbusTcpClient\Utils;

use ModbusTcpClient\Exception\ParseException;

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

    public static int $defaultEndian = self::BIG_ENDIAN_LOW_WORD_FIRST;

    public static function getCurrentEndianness(int $endianness = null): int
    {
        return $endianness === null ? static::$defaultEndian : $endianness;
    }

    public static function applyEndianness(string $binaryData, int $fromEndian = null): string
    {
        $data = $binaryData;
        $fromEndian = Endian::getCurrentEndianness($fromEndian);

        $len = strlen($data);
        if (($fromEndian & Endian::LOW_WORD_FIRST && $len >= 4)) {
            if ($len % 2 !== 0) {
                throw new ParseException('word order can only be changed for data with even number of bytes');
            }
            for ($idxL = 0, $idxH = $len - 1; $idxL < ($len / 2); $idxL += 2, $idxH -= 2) {
                $low1 = $data[$idxL];
                $low2 = $data[$idxL + 1];
                $high1 = $data[$idxH - 1];
                $high2 = $data[$idxH];

                $data[$idxL] = $high1;
                $data[$idxL + 1] = $high2;
                $data[$idxH - 1] = $low1;
                $data[$idxH] = $low2;
            }
        }

        // big endian needs bytes in word reversed
        if (($fromEndian & Endian::BIG_ENDIAN)) {
            $end = $len - 1;
            if ($len % 2 === 0) {
                $end -= 1;
            }
            for ($i = 0; $i < $end; $i += 2) {
                $lb = $data[$i + 1];
                $hb = $data[$i];

                // low byte + high byte
                $data[$i] = $lb;
                $data[$i + 1] = $hb;
            }
        }

        return $data;
    }
}
