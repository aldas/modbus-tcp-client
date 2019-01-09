<?php

namespace ModbusTcpClient\Utils;


use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Exception\OverflowException;
use ModbusTcpClient\Exception\ParseException;

final class Types
{
    const MAX_VALUE_UINT16 = 0xFFFF; // 65535 as dec
    const MIN_VALUE_UINT16 = 0x0;

    const MAX_VALUE_INT16 = 0x7FFF; // 32767 as dec
    const MIN_VALUE_INT16 = -32768; // 0x8000 as hex

    const MAX_VALUE_UINT32 = 0xFFFFFFFF; // 4294967295 as dec
    const MIN_VALUE_UINT32 = 0x0; // 0 as dec

    const MAX_VALUE_INT32 = 0x7FFFFFFF; // 2147483647 as dec
    const MIN_VALUE_INT32 = -2147483648; // 0x80000000 as hex

    const MAX_VALUE_BYTE = 0xFF;
    const MIN_VALUE_BYTE = 0x0;

    private function __construct()
    {
        // no access, this is an utility class
    }

    /**
     * Parse binary string (1 word) with given endianness byte order to 16bit unsigned integer (2 bytes to uint16)
     *
     * @param string $word binary string to be converted to unsigned 16 bit integer
     * @param int $fromEndian byte and word order for modbus binary data
     * @return int
     * @throws \ModbusTcpClient\Exception\InvalidArgumentException
     */
    public static function parseUInt16(string $word, int $fromEndian = null): int
    {
        return unpack(self::getInt16Format($fromEndian), $word)[1];
    }

    private static function getInt16Format(int $fromEndian = null): string
    {
        $fromEndian = Endian::getCurrentEndianness($fromEndian);
        if ($fromEndian & Endian::BIG_ENDIAN) {
            return 'n'; // unsigned short (always 16 bit, big endian byte order)
        }

        if ($fromEndian & Endian::LITTLE_ENDIAN) {
            return 'v'; // unsigned short (always 16 bit, little endian byte order)
        }

        throw new InvalidArgumentException('Unsupported endianness given!');
    }

    /**
     * Parse binary string (1 word) with given endianness byte order to 16bit signed integer (2 bytes to int16)
     *
     * @param string $word binary string to be converted to signed 16 bit integer
     * @param int $fromEndian byte and word order for modbus binary data
     * @return int
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public static function parseInt16(string $word, int $fromEndian = null): int
    {
        $fromEndian = Endian::getCurrentEndianness($fromEndian);
        if ($fromEndian & Endian::BIG_ENDIAN) {
            $format = 'chigh/Clow';
        } elseif ($fromEndian & Endian::LITTLE_ENDIAN) {
            $format = 'Clow/chigh';
        } else {
            throw new InvalidArgumentException('Unsupported endianness given!');
        }
        $byteArray = unpack($format, $word);
        return ($byteArray['high'] << 8) + $byteArray['low'];
    }

    /**
     * Parse binary string (double word) with big endian byte order to 32bit unsigned integer (4 bytes to uint32)
     *
     * NB: On 32bit php and having highest bit set method will return float instead of int value. This is due 32bit php supports only 32bit signed integers
     *
     * @param string $doubleWord binary string to be converted to signed 16 bit integer
     * @param int $fromEndian byte and word order for modbus binary data
     * @return int|float
     * @throws \RuntimeException
     */
    public static function parseUInt32(string $doubleWord, int $fromEndian = null)
    {
        $byteArray = self::getBytesForInt32Parse($doubleWord, $fromEndian);
        if (PHP_INT_SIZE === 4) {
            //can not bit shift safely (for unsigneds) already 16bit value by 16 bits on 32bit arch so shift 15 and multiply by 2
            $byteArray['high'] = ($byteArray['high'] << 15) * 2;
        } else {
            $byteArray['high'] <<= 16;
        }
        return $byteArray['high'] + $byteArray['low'];
    }

    /**
     * Parse binary string (double word) with big endian byte order to 32bit signed integer (4 bytes to int32)
     *
     * @param string $doubleWord binary string to be converted to signed 16 bit integer
     * @param int $fromEndian byte and word order for modbus binary data
     * @return int
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public static function parseInt32(string $doubleWord, int $fromEndian = null): int
    {
        $byteArray = self::getBytesForInt32Parse($doubleWord, $fromEndian);
        $byteArray['high'] = self::uintToSignedInt($byteArray['high']);
        return ($byteArray['high'] << 16) + $byteArray['low'];
    }

    private static function getBytesForInt32Parse(string $doubleWord, int $endianness = null)
    {
        $endianness = Endian::getCurrentEndianness($endianness);

        $left = 'high';
        $right = 'low';
        if ($endianness & Endian::LOW_WORD_FIRST) {
            $left = 'low';
            $right = 'high';
        }

        if ($endianness & Endian::BIG_ENDIAN) {
            $format = 'n';
        } elseif ($endianness & Endian::LITTLE_ENDIAN) {
            $format = 'v';
        } else {
            throw new InvalidArgumentException('Unsupported endianness given!');
        }

        return unpack("{$format}{$left}/{$format}{$right}", $doubleWord);
    }

    /**
     * Convert 2/4/8 byte into a signed integer. This is needed to make code 32bit php and 64bit compatible as Pack function
     * does not have options to convert big endian signed integers
     * taken from http://stackoverflow.com/q/13322327/2514290
     * @param $uint
     * @param bool $bitSize
     * @return int
     */
    private static function uintToSignedInt(int $uint, int $bitSize = 16): int
    {
        if ($bitSize === 16 && ($uint & 0x8000) > 0) {
            // This is a negative number.  Invert the bits and add 1 and add negative sign
            $uint = -((~$uint & 0xFFFF) + 1);
        } elseif ($bitSize === 32 && ($uint & 0x80000000) > 0) {
            // This is a negative number.  Invert the bits and add 1 and add negative sign
            $uint = -((~$uint & 0xFFFFFFFF) + 1);
        } elseif ($bitSize === 64 && ($uint & 0x8000000000000000) > 0) {
            // This is a negative number.  Invert the bits and add 1 and add negative sign
            $uint = -((~$uint & 0xFFFFFFFFFFFFFFFF) + 1);
        }
        return $uint;
    }

    /**
     * Parse binary string (1 char) to 8bit unsigned integer (1 bytes to uint8)
     *
     * @param string $char binary string to be converted to unsigned 8 bit unsigned integer
     * @return int
     */
    public static function parseByte(string $char): int
    {
        return unpack('C', $char)[1];
    }

    /**
     * Parse binary string to array of unsigned integers (uint8)
     *
     * @param $binaryData string binary string to be converted to array of unsigned 8 bit unsigned integers
     * @return int[]
     */
    public static function parseByteArray(string $binaryData): array
    {
        return array_values(unpack('C*', $binaryData));
    }

    /**
     * Convert array of PHP data to array of bytes. Each element of $data is converted to 1 byte (usigned int8)
     *
     * @param array $data
     * @return string
     */
    public static function byteArrayToByte(array $data): string
    {
        return pack('C*', ...$data);
    }

    public static function booleanArrayToByteArray(array $booleans): array
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

    public static function binaryStringToBooleanArray(string $binary): array
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

    /**
     * Check if N-th bit is set in data. NB: Bits are counted from 0 and right to left.
     *
     * @param $data int|string data from where bit is checked
     * @param $bit int to be checked
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function isBitSet($data, int $bit): bool
    {
        if (null === $data) {
            return false;
        } elseif (is_string($data)) {
            $nthByte = (int)($bit / 8);
            $bit %= 8;
            $offset = (strlen($data) - 1) - $nthByte;
            $data = ord($data[$offset]);
        } elseif (is_int($data)) {
            /**
             * From: http://php.net/manual/en/language.operators.bitwise.php
             * Warning: Shifting integers by values greater than or equal to the system long integer width results
             * in undefined behavior. In other words, don't shift more than 31 bits on a 32-bit system,
             * and don't shift more than 63 bits on a 64-bit system.
             */
            if (PHP_INT_SIZE === 4 && $bit > 31) {
                throw new InvalidArgumentException('On 32bit PHP bit shifting more than 31 bit is not possible as int size is 32 bytes');
            }

            if (PHP_INT_SIZE === 8 && $bit > 63) {
                throw new InvalidArgumentException('On 64bit PHP bit shifting more than 63 bit is not possible as int size is 64 bytes');
            }
        }

        return 1 === (($data >> $bit) & 1);
    }

    /**
     * Parse binary string representing real in given endianness to float (double word/4 bytes to float)
     *
     * @param string $binaryData binary byte string to be parsed to float
     * @param int $fromEndian byte and word order for modbus binary data
     * @return float
     * @throws \RuntimeException
     */
    public static function parseFloat(string $binaryData, int $fromEndian = null): float
    {
        $fromEndian = Endian::getCurrentEndianness($fromEndian);
        if ($fromEndian & Endian::LOW_WORD_FIRST) {
            $binaryData = substr($binaryData, 2, 2) . substr($binaryData, 0, 2);
        }

        if ($fromEndian & Endian::BIG_ENDIAN) {
            $format = 'N';
        } elseif ($fromEndian & Endian::LITTLE_ENDIAN) {
            $format = 'V';
        } else {
            throw new InvalidArgumentException('Unsupported endianness given!');
        }
        // reverse words if needed
        // parse as uint32 to binary big/little endian,
        // pack to machine order int 32,
        // unpack to machine order float
        $pack = unpack($format, $binaryData)[1];
        return unpack('f', pack('L', $pack))[1];
    }

    /**
     * Parse binary string representing 64 bit unsigned integer to 64bit unsigned integer in given endianness (quad word/8 bytes to 64bit int)
     *
     * @param string $binaryData binary string representing 64 bit unsigned integer in big endian order
     * @param int $fromEndian byte and word order for modbus binary data
     * @return int
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public static function parseUInt64(string $binaryData, int $fromEndian = null): int
    {
        if (strlen($binaryData) !== 8) {
            throw new ParseException('binaryData must be 8 bytes in length');
        }
        if (PHP_INT_SIZE !== 8) {
            throw new ParseException('64-bit format codes are not available for 32-bit versions of PHP');
        }

        $fromEndian = Endian::getCurrentEndianness($fromEndian);
        if ($fromEndian & Endian::LOW_WORD_FIRST) {
            $binaryData = implode('', array_reverse(str_split($binaryData, 2)));
        }

        if ($fromEndian & Endian::BIG_ENDIAN) {
            $format = 'J';
        } elseif ($fromEndian & Endian::LITTLE_ENDIAN) {
            $format = 'P';
        } else {
            throw new InvalidArgumentException('Unsupported endianness given!');
        }

        $result = unpack($format, $binaryData)[1];

        if ($result < 0) {
            $value = unpack('H*', $binaryData)[1];
            throw new OverflowException('64-bit PHP supports only up to 63-bit signed integers. Current input has 64th bit set and overflows. Hex: ' . $value);
        }
        return $result;
    }

    /**
     * Parse binary string representing 64 bit signed integer to 64bit signed integer in given endianness  (quad word/8 bytes to 64bit int)
     *
     * @param string $binaryData binary string representing 64 bit signed integer in big endian order
     * @param int $fromEndian byte and word order for modbus binary data
     * @return int
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public static function parseInt64(string $binaryData, int $fromEndian = null)
    {
        if (strlen($binaryData) !== 8) {
            throw new ParseException('binaryData must be 8 bytes in length');
        }
        if (PHP_INT_SIZE !== 8) {
            throw new ParseException('64-bit format codes are not available for 32-bit versions of PHP');
        }

        $fromEndian = Endian::getCurrentEndianness($fromEndian);
        if ($fromEndian & Endian::LOW_WORD_FIRST) {
            $binaryData = implode('', array_reverse(str_split($binaryData, 2)));
        }

        if ($fromEndian & Endian::BIG_ENDIAN) {
            $format = 'J';
        } elseif ($fromEndian & Endian::LITTLE_ENDIAN) {
            $format = 'P';
        } else {
            throw new InvalidArgumentException('Unsupported endianness given!');
        }
        return self::uintToSignedInt(unpack($format, $binaryData)[1], 64);
    }

    /**
     * Parse ascii string from registers to utf-8 string. Supports extended ascii codes ala 'ø' (decimal 248)
     *
     * @param string $binaryData binary string representing register (words) contents
     * @param int $length number of characters to parse from data
     * @param int $fromEndian byte and word order for modbus binary data
     * @return string
     */
    public static function parseAsciiStringFromRegister(string $binaryData, int $length = 0, int $fromEndian = null): string
    {
        // 'ASCII' is needed to for extended ascii characters as 'ø' (decimal 248)
        return static::parseStringFromRegister($binaryData, $length, 'ASCII', $fromEndian);
    }

    /**
     * Parse string from registers to utf-8 string.
     *
     * @param string $binaryData binary string representing register (words) contents
     * @param int $length number of characters to parse from data
     * @param string $fromEncoding
     * @param int $fromEndian byte and word order for modbus binary data
     * @return string
     */
    public static function parseStringFromRegister(string $binaryData, int $length, string $fromEncoding = null, int $fromEndian = null): string
    {
        $data = $binaryData;

        $fromEndian = Endian::getCurrentEndianness($fromEndian);
        if ($fromEndian & Endian::BIG_ENDIAN) {

            $data = '';
            // big endian needs bytes in word reversed
            foreach (str_split($binaryData, 2) as $word) {
                if (isset($word[1])) {
                    $data .= $word[1] . $word[0]; // low byte + high byte
                } else {
                    $data .= $word[0]; // assume that last single byte is in correct place
                }
            }
        }

        $rawLen = strlen($data);
        if (!$length || $length > $rawLen) {
            $length = strlen($data);
        }

        $result = unpack("Z{$length}", $data)[1];

        if ($fromEncoding !== null) {
            $result = mb_convert_encoding($result, 'UTF-8', $fromEncoding);
        }

        return $result;

    }

    /**
     * Convert Php integer to modbus register (2 bytes of data) in big endian byte order
     *
     * @param int $data integer to be converted to register/word (binary string of 2 bytes)
     * @return string binary string with big endian byte order
     */
    public static function toRegister($data): string
    {
        $data &= 0xFFFF;
        return pack('n', $data);
    }

    /**
     * Convert Php data as it would be 1 byte to binary string (1 char)
     *
     * @param int $data 1 bit integer to be converted to binary byte string
     * @return string binary string with length of 1 char
     */
    public static function toByte($data): string
    {
        return pack('C', $data);
    }

    /**
     * Convert Php data as it would be 16 bit integer to binary string with big endian byte order
     *
     * @param int $data 16 bit integer to be converted to binary string (1 word)
     * @param int $toEndian byte and word order for modbus binary data
     * @param bool $doRangeCheck should min/max range check be done for data
     * @return string binary string with big endian byte order
     */
    public static function toInt16($data, int $toEndian = null, bool $doRangeCheck = true): string
    {
        if ($doRangeCheck && ($data < self::MIN_VALUE_INT16 || $data > self::MAX_VALUE_INT16)) {
            throw new OverflowException('Data out of int16 range (-32768...32767)! Given: ' . $data);
        }

        return pack(self::getInt16Format($toEndian), $data);
    }

    /**
     * Convert Php data as it would be unsigned 16 bit integer to binary string in given endianess
     *
     * @param int $data 16 bit integer to be converted to binary string (1 word)
     * @param int $toEndian byte and word order for modbus binary data
     * @param bool $doRangeCheck should min/max range check be done for data
     * @return string binary string with big endian byte order
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public static function toUint16($data, int $toEndian = null, bool $doRangeCheck = true): string
    {
        if ($doRangeCheck && ($data < self::MIN_VALUE_UINT16 || $data > self::MAX_VALUE_UINT16)) {
            throw new OverflowException('Data out of uint16 range (0...65535)! Given: ' . $data);
        }

        return pack(self::getInt16Format($toEndian), $data);
    }

    /**
     * Convert Php data as it would be 32 bit integer to binary string with given endianness order
     *
     * @param int $data 32 bit integer to be converted to binary string (double word)
     * @param int $toEndian byte and word order for modbus binary data
     * @param bool $doRangeCheck should min/max range check be done for data
     * @return string binary string with big endian byte order
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public static function toInt32($data, int $toEndian = null, bool $doRangeCheck = true): string
    {
        if ($doRangeCheck && ($data < self::MIN_VALUE_INT32 || $data > self::MAX_VALUE_INT32)) {
            throw new OverflowException('Data out of int32 range (-2147483648...2147483647)! Given: ' . $data);
        }
        return static::toInt32Internal($data, $toEndian);
    }

    /**
     * Convert Php data as it would be unsigned 32 bit integer to binary string with given endianness order
     *
     * @param int $data 32 bit unsigned integer to be converted to binary string (double word)
     * @param int $toEndian byte and word order for modbus binary data
     * @param bool $doRangeCheck should min/max range check be done for data
     * @return string binary string with big endian byte order
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public static function toUint32($data, int $toEndian = null, bool $doRangeCheck = true): string
    {
        if ($doRangeCheck && ($data < self::MIN_VALUE_UINT32 || $data > self::MAX_VALUE_UINT32)) {
            throw new OverflowException('Data out of int32 range (0...4294967295)! Given: ' . $data);
        }
        return static::toInt32Internal($data, $toEndian);
    }

    private static function toInt32Internal($data, $endianness = null): string
    {
        $words = [
            ($data >> 16) & 0xFFFF,
            $data & 0xFFFF
        ];

        $endianness = Endian::getCurrentEndianness($endianness);
        if ($endianness & Endian::LOW_WORD_FIRST) {
            $words = [$words[1], $words[0]];
        }

        $format = self::getInt16Format($endianness);
        return pack("{$format}*", ...$words);
    }

    /**
     * Convert Php data as it would be 64 bit integer to binary string with given endianness order
     *
     * @param int $data 64 bit integer to be converted to binary string (quad word)
     * @param int $toEndian byte and word order for modbus binary data
     * @return string binary string with big endian byte order
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public static function toInt64($data, int $toEndian = null): string
    {
        $words = [
            ($data >> 48) & 0xFFFF,
            ($data >> 32) & 0xFFFF,
            ($data >> 16) & 0xFFFF,
            $data & 0xFFFF
        ];

        $toEndian = Endian::getCurrentEndianness($toEndian);
        if ($toEndian & Endian::LOW_WORD_FIRST) {
            $words = [$words[3], $words[2], $words[1], $words[0]];
        }

        $format = self::getInt16Format($toEndian);
        return pack("{$format}*", ...$words);
    }

    /**
     * Convert Php data as it would be 64 bit unsigned integer to binary string with given endianness order
     *
     * @param int $data 64 bit integer to be converted to binary string (quad word)
     * @param int $toEndian byte and word order for modbus binary data
     * @return string binary string with big endian byte order
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public static function toUint64($data, int $toEndian = null, bool $doRangeCheck = true): string
    {
        if ($doRangeCheck && $data < 0) {
            throw new OverflowException('Data out of uint64 range (0...9223372036854775807)! Given: ' . $data);
        }
        // php has actually only signed integers so we can actually use 63bits of 64bit of unsigned int value
        return static::toInt64($data, $toEndian);
    }

    /**
     * Convert Php data as it would be float to binary string with given endian order
     *
     * @param float $float float to be converted to binary byte string
     * @param int $toEndian byte and word order for modbus binary data
     * @return string binary string with big endian byte order
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public static function toReal($float, int $toEndian = null): string
    {
        //pack to machine order float, unpack to machine order uint32, pack uint32 to binary big endian
        //from php git seems that some day there will be 'g' and 'G' modifiers for float LE/BE conversion
        return self::toInt32(unpack('L', pack('f', $float))[1], $toEndian, false);
    }

    /**
     * Convert PHP string to binary string suitable for modbus packet
     *
     * @param string $string string to convert
     * @param int $registersCount number of registers to hold string bytes
     * @param string|null $toEncoding in which string encoding data is expected
     * @param int|null $toEndian in which endianess and word order resulting binary string should be
     * @return string
     */
    public static function toString($string, int $registersCount, string $toEncoding = null, int $toEndian = null): string
    {
        if ($toEncoding !== null) {
            // use 'cp1252' as encoding if you just need extended ASCII chars i.e. chars like 'ø'
            $string = mb_convert_encoding($string, $toEncoding);
        }
        $byteCount = $registersCount * 2;

        $raw = '';
        if (!empty($string)) {
            $string = substr($string, 0, $byteCount - 1);
            $words = str_split($string, 2);

            $toEndian = Endian::getCurrentEndianness($toEndian);
            if ($toEndian & Endian::LOW_WORD_FIRST) {
                $words = array_reverse($words);
            }

            if ($toEndian & Endian::BIG_ENDIAN && !empty($words)) {
                // big endian needs bytes in word reversed
                foreach ($words as &$word) {
                    if (isset($word[1])) {
                        $word = $word[1] . $word[0]; // low byte + high byte
                    } else {
                        $word = "\x00" . $word[0];
                    }
                }
            }
            $raw = implode('', $words);
        }

        return pack("a{$byteCount}", $raw);
    }

}