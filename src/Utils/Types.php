<?php

namespace ModbusTcpClient\Utils;


use ModbusTcpClient\ModbusException;

final class Types
{
    const MAX_VALUE_UINT16 = 0xFFFF; //65535
    const MIN_VALUE_UINT16 = 0x0;

    const MAX_VALUE_INT16 = 0x7FFF;
    const MIN_VALUE_INT16 = -32768; // 0x8000 as hex

    const MAX_VALUE_BYTE = 0xFF;
    const MIN_VALUE_BYTE = 0x0;

    private function __construct()
    {
        // no access, this is an utility class
    }

    /**
     * Convert Php data as it would be 16 bit integer to binary string with big endian byte order
     *
     * @param int $data 16 bit integer to be converted to binary string (1 word)
     * @param int $endianness byte and word order for modbus binary data
     * @return string binary string with big endian byte order
     * @throws \ModbusTcpClient\ModbusException
     */
    public static function toInt16($data, $endianness = null)
    {
        return pack(self::getInt16Format($endianness), $data);
    }

    /**
     * Parse binary string (1 word) with given endianness byte order to 16bit unsigned integer (2 bytes to uint16)
     *
     * @param string $word binary string to be converted to unsigned 16 bit integer
     * @param int $endianness byte and word order for modbus binary data
     * @return int
     * @throws \ModbusTcpClient\ModbusException
     */
    public static function parseUInt16($word, $endianness = null)
    {
        return unpack(self::getInt16Format($endianness), $word)[1];
    }

    private static function getInt16Format($endianness)
    {
        $endianness = Endian::getCurrentEndianness($endianness);
        if ($endianness & Endian::BIG_ENDIAN) {
            return 'n';
        } elseif ($endianness & Endian::LITTLE_ENDIAN) {
            return 'v';
        } else {
            throw new ModbusException('Unsupported endianness given!');
        }
    }

    /**
     * Parse binary string (1 word) with given endianness byte order to 16bit signed integer (2 bytes to int16)
     *
     * @param string $word binary string to be converted to signed 16 bit integer
     * @param int $endianness byte and word order for modbus binary data
     * @return int
     * @throws \ModbusTcpClient\ModbusException
     */
    public static function parseInt16($word, $endianness = null)
    {
        $endianness = Endian::getCurrentEndianness($endianness);
        if ($endianness & Endian::BIG_ENDIAN) {
            $format = 'chigh/Clow';
        } elseif ($endianness & Endian::LITTLE_ENDIAN) {
            $format = 'Clow/chigh';
        } else {
            throw new ModbusException('Unsupported endianness given!');
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
     * @param int $endianness byte and word order for modbus binary data
     * @return int|float
     * @throws \RuntimeException
     */
    public static function parseUInt32($doubleWord, $endianness = null)
    {
        $byteArray = self::getBytesForInt32Parse($doubleWord, $endianness);
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
     * @param int $endianness byte and word order for modbus binary data
     * @return int
     * @throws \ModbusTcpClient\ModbusException
     */
    public static function parseInt32($doubleWord, $endianness = null)
    {
        $byteArray = self::getBytesForInt32Parse($doubleWord, $endianness);
        $byteArray['high'] = self::uint16TosignedInt16($byteArray['high']);
        return ($byteArray['high'] << 16) + $byteArray['low'];
    }

    private static function getBytesForInt32Parse($doubleWord, $endianness)
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
            throw new ModbusException('Unsupported endianness given!');
        }

        return unpack("{$format}{$left}/{$format}{$right}", $doubleWord);
    }

    /**
     * Convert 2 byte into a signed integer. This is needed to make code 32bit php and 64bit compatible
     * taken from http://stackoverflow.com/q/13322327/2514290
     * @param $uint16
     * @return int
     */
    private static function uint16TosignedInt16($uint16)
    {
        if (($uint16 & 0x8000) > 0) {
            // This is a negative number.  Invert the bits and add 1 and add negative sign
            $uint16 = -((~$uint16 & 0xFFFF) + 1);
        }
        return $uint16;
    }

    /**
     * Convert Php data as it would be 32 bit integer to binary string with given endianness order
     *
     * @param int $data 32 bit integer to be converted to binary string (double word)
     * @param int $endianness byte and word order for modbus binary data
     * @return string binary string with big endian byte order
     * @throws \ModbusTcpClient\ModbusException
     */
    public static function toInt32($data, $endianness = null)
    {
        $highWord = self::toInt16(($data >> 16) & 0xFFFF, $endianness);
        $lowWord = self::toInt16($data & 0xFFFF, $endianness);

        $endianness = Endian::getCurrentEndianness($endianness);
        if ($endianness & Endian::LOW_WORD_FIRST) {
            return $lowWord . $highWord;
        }
        return $highWord . $lowWord;
    }

    /**
     * Convert Php data as it would be 1 byte to binary string (1 char)
     *
     * @param int $data 1 bit integer to be converted to binary byte string
     * @return string binary string with length of 1 char
     */
    public static function toByte($data)
    {
        return pack('C', $data);
    }

    /**
     * Parse binary string (1 char) to 8bit unsigned integer (1 bytes to uint8)
     *
     * @param string $char binary string to be converted to unsigned 8 bit unsigned integer
     * @return int
     */
    public static function parseByte($char)
    {
        return unpack('C', $char)[1];
    }

    /**
     * Parse binary string to array of unsigned integers (uint8)
     *
     * @param $binaryData string binary string to be converted to array of unsigned 8 bit unsigned integers
     * @return int[]
     */
    public static function parseByteArray($binaryData)
    {
        return array_values(unpack('C*', $binaryData));
    }

    /**
     * Convert array of PHP data to array of bytes. Each element of $data is converted to 1 byte (usigned int8)
     *
     * @param array $data
     * @return string
     */
    public static function byteArrayToByte(array $data)
    {
        return pack('C*', ...$data);
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
                return Types::toInt16($elem);
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

    /**
     * Check if N-th bit is set in data. NB: Bits are counted from 0 and right to left.
     *
     * @param $data int|string data from where bit is checked
     * @param $bit int to be checked
     * @return bool
     * @throws \InvalidArgumentException
     */
    public static function isBitSet($data, $bit)
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
                throw new \InvalidArgumentException('On 32bit PHP bit shifting more than 31 bit is not possible as int size is 32 bytes');
            } elseif (PHP_INT_SIZE === 8 && $bit > 63) {
                throw new \InvalidArgumentException('On 64bit PHP bit shifting more than 63 bit is not possible as int size is 64 bytes');
            }
        }

        return 1 === (($data >> $bit) & 1);
    }

    /**
     * Convert Php data as it would be float to binary string with given endian order
     *
     * @param float $float float to be converted to binary byte string
     * @param int $endianness byte and word order for modbus binary data
     * @return string binary string with big endian byte order
     * @throws \ModbusTcpClient\ModbusException
     */
    public static function toReal($float, $endianness = null)
    {
        //pack to machine order float, unpack to machine order uint32, pack uint32 to binary big endian
        //from php git seems that some day there will be 'g' and 'G' modifiers for float LE/BE conversion
        return self::toInt32(unpack('L', pack('f', $float))[1], $endianness);
    }

    /**
     * Parse binary string representing real in given endianness to float (double word/4 bytes to float)
     *
     * @param string $binaryData binary byte string to be parsed to float
     * @param int $endianness byte and word order for modbus binary data
     * @return float
     * @throws \RuntimeException
     */
    public static function parseFloat($binaryData, $endianness = null)
    {
        $endianness = Endian::getCurrentEndianness($endianness);
        if ($endianness & Endian::LOW_WORD_FIRST) {
            $binaryData = substr($binaryData, 2, 2) . substr($binaryData, 0, 2);
        }

        if ($endianness & Endian::BIG_ENDIAN) {
            $format = 'N';
        } elseif ($endianness & Endian::LITTLE_ENDIAN) {
            $format = 'V';
        } else {
            throw new ModbusException('Unsupported endianness given!');
        }
        // reverse words if needed
        // parse as uint32 to binary big/little endian,
        // pack to machine order int 32,
        // unpack to machine order float
        $pack = unpack($format, $binaryData)[1];
        return unpack('f', pack('L', $pack))[1];
    }

    /**
     * Parse binary string representing 64 bit unsigned integer in given endianness to 64bit unsigned integer (quad word/8 bytes to 64bit int)
     *
     * @param string $binaryData binary string representing 64 bit unsigned integer in big endian order
     * @param int $endianness byte and word order for modbus binary data
     * @return int
     * @throws \RangeException
     * @throws \LengthException
     * @throws \OutOfRangeException
     * @throws \ModbusTcpClient\ModbusException
     */
    public static function parseUInt64($binaryData, $endianness = null)
    {
        if (strlen($binaryData) !== 8) {
            throw new \LengthException('binaryData must be 8 bytes in length');
        }
        if (PHP_INT_SIZE !== 8) {
            throw new \OutOfRangeException('64-bit format codes are not available for 32-bit versions of PHP');
        }

        $endianness = Endian::getCurrentEndianness($endianness);
        if ($endianness & Endian::LOW_WORD_FIRST) {
            $binaryData = implode('', array_reverse(str_split($binaryData, 2)));
        }

        if ($endianness & Endian::BIG_ENDIAN) {
            $format = 'J';
        } elseif ($endianness & Endian::LITTLE_ENDIAN) {
            $format = 'P';
        } else {
            throw new ModbusException('Unsupported endianness given!');
        }

        $result = unpack($format, $binaryData)[1];

        if ($result < 0) {
            throw new \RangeException('64-bit PHP supports only up to 63-bit signed integers. Current input has 64th bit set and overflows');
        }
        return $result;
    }

    /**
     * Parse ascii string from registers to utf-8 string. Supports extended ascii codes ala 'ø' (decimal 248)
     *
     * @param string $binaryData binary string representing register (words) contents
     * @param int $length number of characters to parse from data
     * @param int $endianness byte and word order for modbus binary data
     * @return string
     */
    public static function parseAsciiStringFromRegister($binaryData, $length = 0, $endianness = null)
    {
        $data = $binaryData;

        $endianness = Endian::getCurrentEndianness($endianness);
        if ($endianness & Endian::BIG_ENDIAN) {

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

        if (!$length) {
            $length = strlen($data);
        }

        $result = unpack("Z{$length}", $data)[1];

        // needed to for extended ascii characters as 'ø' (decimal 248)
        $result = mb_convert_encoding($result, 'UTF-8', 'ASCII');

        return $result;
    }

}