<?php

namespace ModbusTcpClient\Utils;


class Types
{
    const MAX_VALUE_UINT16 = 0xFFFF; //65535
    const MIN_VALUE_UINT16 = 0x0;

    const MAX_VALUE_INT16 = 0x7FFF;
    const MIN_VALUE_INT16 = -32768; // 0x8000 as hex

    const MAX_VALUE_BYTE = 0xFF;
    const MIN_VALUE_BYTE = 0x0;

    /**
     * Convert Php data as it would be 16 bit integer to binary string with big endian byte order
     *
     * @param int $data 16 bit integer to be converted to binary string (1 word)
     * @return string binary string with big endian byte order
     */
    public static function toInt16BE($data)
    {
        return pack('n', $data);
    }

    /**
     * Parse binary string (1 word) with big endian byte order to 16bit unsigned integer (2 bytes to uint16)
     *
     * @param string $word binary string to be converted to unsigned 16 bit integer
     * @return int
     */
    public static function parseUInt16BE($word)
    {
        return unpack('n', $word)[1];
    }

    /**
     * Parse binary string (1 word) with big endian byte order to 16bit signed integer (2 bytes to int16)
     *
     * @param string $word binary string to be converted to signed 16 bit integer
     * @return int
     */
    public static function parseInt16BE($word)
    {
        //TODO raw bit operations would be faster than unpack + array accessing?
        $byteArray = unpack('chigh/Clow', $word);
        return ($byteArray['high'] << 8) + $byteArray['low'];
    }

    /**
     * Parse binary string (double word) with big endian byte order to 32bit unsigned integer (4 bytes to uint32)
     *
     * @param string $doubleWord binary string to be converted to signed 16 bit integer
     * @return int
     * @throws \RuntimeException
     */
    public static function parseUInt32BE($doubleWord)
    {
        //in network low byte is sent first and after that high byte (Big endian high word first)
        $byteArray = unpack('Cb3/Cb2/Cb1/Cb0', $doubleWord);
        $b1 = (float)($byteArray['b1'] << 23) * 2; //can not bit shift safely (for unsigneds) 24 bits on 32bit arch so multiply by 2
        return $b1 + ($byteArray['b0'] << 16) + ($byteArray['b3'] << 8) + $byteArray['b2'];
    }

    /**
     * Parse binary string (double word) with big endian byte order to 32bit signed integer (4 bytes to int32)
     *
     * @param string $doubleWord binary string to be converted to signed 16 bit integer
     * @return int
     */
    public static function parseInt32BE($doubleWord)
    {
        //TODO raw bit operations would be faster than unpack + array accessing?
        $byteArray = unpack('nlow/nhigh', $doubleWord);
        $byteArray['high'] = self::uint16TosignedInt16($byteArray['high']);
        //in network low byte is sent first and after that high byte (Big endian)
        return ($byteArray['high'] << 16) + $byteArray['low'];
    }

    /**
     * Convert 2 byte into a signed integer. This is needed to make code 32bit php and 64bit compatible
     * taken from http://stackoverflow.com/q/13322327/2514290
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
     * Convert Php data as it would be 32 bit integer to binary string with big endian byte order
     *
     * @param int $data 32 bit integer to be converted to binary string (double word)
     * @return string binary string with big endian byte order
     */
    public static function toInt32BE($data)
    {
        //http://www.simplymodbus.ca/FAQ.htm#Order
        //dec: 2923517522 is in hex: AE415652, low word being 5652, high word AE41
        //so in network 5652 AE41 should be sent. low word first (Big endian)
        $highWord = self::toInt16BE(($data >> 16) & 0xFFFF); // get last 2 bytes
        $lowWord = self::toInt16BE($data & 0xFFFF); // get first 2 bytes
        return $lowWord . $highWord;
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
                return Types::toInt16BE($elem);
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
     * Convert Php data as it would be float to binary string with big endian order
     *
     * @param float $float float to be converted to binary byte string
     * @return string binary string with big endian byte order
     */
    public static function toReal($float)
    {
        //pack to machine order float, unpack to machine order uint32, pack uint32 to binary big endian
        //from php git seems that some day there will be 'g' and 'G' modifiers for float LE/BE conversion
        return self::toInt32BE(unpack('L', pack('f', $float))[1]);
    }

    /**
     * Parse binary string representing real in big endian order to float (double word/4 bytes to float)
     *
     * @param string $binaryData binary byte string to be parsed to float
     * @return float
     * @throws \RuntimeException
     */
    public static function parseFloat($binaryData)
    {
        // parse as uint32 to binary big endian, pack to machine order int 32, unpack to machine order float
        $pack = self::parseUInt32BE($binaryData);
        return unpack('f', pack('L', $pack))[1];
    }

    /**
     * Parse binary string representing 64 bit unsigned integer in big endian order to 64bit unsigned integer (quad word/8 bytes to 64bit int)
     *
     * @param string $binaryData binary string representing 64 bit unsigned integer in big endian order
     * @return int
     * @throws \LengthException
     * @throws \RuntimeException
     * @throws \RangeException
     * @throws \OutOfRangeException
     */
    public static function parseUInt64($binaryData)
    {
        if (strlen($binaryData) !== 8) {
            throw new \LengthException('binaryData must be 8 bytes in length');
        }
        if (PHP_INT_SIZE !== 8) {
            throw new \OutOfRangeException('64-bit format codes are not available for 32-bit versions of PHP');
        }
        $low = static::parseUInt32BE(substr($binaryData, 4));

        $highestByte = ord($binaryData[2]);
        if ($highestByte > 0x80 || ($highestByte === 0x80 && $low > 0)) {
            throw new \RangeException('64-bit PHP supports only up to 63-bit signed integers. Current input has 64th bit set and overflows');
        }

        $high = static::parseUInt32BE(substr($binaryData, 0, 4));
        return (($high << 31) * 2) + $low;
    }
}