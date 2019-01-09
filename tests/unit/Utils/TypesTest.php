<?php

namespace Tests\Utils;


use ModbusTcpClient\Utils\Endian;
use ModbusTcpClient\Utils\Types;
use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{
    public function testShouldParseUint16FromWordWithBigEndian()
    {
        $this->assertEquals(1, Types::parseUInt16("\x00\x01", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(1, Types::parseUInt16("\x00\x01", Endian::BIG_ENDIAN));
        $this->assertEquals(32767, Types::parseUInt16("\x7F\xFF", Endian::BIG_ENDIAN));
        $this->assertEquals(32768, Types::parseUInt16("\x80\x00", Endian::BIG_ENDIAN));
        $this->assertEquals(65535, Types::parseUInt16("\xFF\xFF", Endian::BIG_ENDIAN));
    }

    public function testShouldParseUint16FromWordWithLittleEndian()
    {
        $this->assertEquals(1, Types::parseUInt16("\x01\x00", Endian::LITTLE_ENDIAN));
        $this->assertEquals(32767, Types::parseUInt16("\xFF\x7F", Endian::LITTLE_ENDIAN));
        $this->assertEquals(32768, Types::parseUInt16("\x00\x80", Endian::LITTLE_ENDIAN));
        $this->assertEquals(65535, Types::parseUInt16("\xFF\xFF", Endian::LITTLE_ENDIAN));
    }

    public function testShouldParseInt16FromWordWithBigEndian()
    {
        $this->assertEquals(0, Types::parseInt16("\x00\x00", Endian::BIG_ENDIAN));
        $this->assertEquals(1, Types::parseInt16("\x00\x01", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(1, Types::parseInt16("\x00\x01", Endian::BIG_ENDIAN));
        $this->assertEquals(-1, Types::parseInt16("\xFF\xFF", Endian::BIG_ENDIAN));
        $this->assertEquals(-32768, Types::parseInt16("\x80\x00", Endian::BIG_ENDIAN));
        $this->assertEquals(32767, Types::parseInt16("\x7F\xFF", Endian::BIG_ENDIAN));
    }

    public function testShouldParseInt16FromWordWithLittleEndian()
    {
        $this->assertEquals(0, Types::parseInt16("\x00\x00", Endian::LITTLE_ENDIAN));
        $this->assertEquals(1, Types::parseInt16("\x01\x00", Endian::LITTLE_ENDIAN | Endian::LOW_WORD_FIRST));
        $this->assertEquals(1, Types::parseInt16("\x01\x00", Endian::LITTLE_ENDIAN));
        $this->assertEquals(-1, Types::parseInt16("\xFF\xFF", Endian::LITTLE_ENDIAN));
        $this->assertEquals(-32768, Types::parseInt16("\x00\x80", Endian::LITTLE_ENDIAN));
        $this->assertEquals(32767, Types::parseInt16("\xFF\x7F", Endian::LITTLE_ENDIAN));
    }

    public function testShouldParseUInt32FromDoubleWordAsBigEndianLowWordFirst()
    {
        $this->assertEquals(0, Types::parseUInt32("\x00\x00\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(1, Types::parseUInt32("\x00\x01\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(2147483647, Types::parseUInt32("\xFF\xFF\x7F\xFF", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(2147483648, Types::parseUInt32("\x00\x00\x80\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(4294967295, Types::parseUInt32("\xFF\xFF\xFF\xFF", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(133124, Types::parseUInt32("\x08\x04\x00\x02", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(565, Types::parseUInt32("\x02\x35\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));

        if (PHP_INT_SIZE === 8) {
            $this->assertTrue(is_int(Types::parseUInt32("\xFF\xFF\xFF\xFF", Endian::BIG_ENDIAN_LOW_WORD_FIRST)));
            $this->assertTrue(is_int(Types::parseUInt32("\x00\x01\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST)));
        } else {
            $this->assertTrue(is_float(Types::parseUInt32("\xFF\xFF\xFF\xFF", Endian::BIG_ENDIAN_LOW_WORD_FIRST))); // is converted to float to hold this big value

            $this->assertTrue(is_int(Types::parseUInt32("\xFF\xFF\x7F\xFF", Endian::BIG_ENDIAN_LOW_WORD_FIRST)));
            $this->assertTrue(is_int(Types::parseUInt32("\x00\x01\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST)));
        }
    }

    public function testShouldParseUInt32FromDoubleWordAsBigEndian()
    {
        $this->assertEquals(0, Types::parseUInt32("\x00\x00\x00\x00", Endian::BIG_ENDIAN));
        $this->assertEquals(1, Types::parseUInt32("\x00\x00\x00\x01", Endian::BIG_ENDIAN));
        $this->assertEquals(2147483647, Types::parseUInt32("\x7F\xFF\xFF\xFF", Endian::BIG_ENDIAN));
        $this->assertEquals(2147483648, Types::parseUInt32("\x80\x00\x00\x00", Endian::BIG_ENDIAN));
        $this->assertEquals(4294967295, Types::parseUInt32("\xFF\xFF\xFF\xFF", Endian::BIG_ENDIAN));
        $this->assertEquals(133124, Types::parseUInt32("\x00\x02\x08\x04", Endian::BIG_ENDIAN));
        $this->assertEquals(565, Types::parseUInt32("\x00\x00\x02\x35", Endian::BIG_ENDIAN));

        if (PHP_INT_SIZE === 8) {
            $this->assertTrue(is_int(Types::parseUInt32("\xFF\xFF\xFF\xFF", Endian::BIG_ENDIAN)));
            $this->assertTrue(is_int(Types::parseUInt32("\x00\x00\x00\x01", Endian::BIG_ENDIAN)));
        } else {
            $this->assertTrue(is_float(Types::parseUInt32("\xFF\xFF\xFF\xFF", Endian::BIG_ENDIAN))); // is converted to float to hold this big value

            $this->assertTrue(is_int(Types::parseUInt32("\x7F\xFF\xFF\xFF", Endian::BIG_ENDIAN)));
            $this->assertTrue(is_int(Types::parseUInt32("\x00\x00\x00\x01", Endian::BIG_ENDIAN)));
        }
    }

    public function testShouldParseUInt32FromDoubleWordAsLittleEndian()
    {
        $this->assertEquals(0, Types::parseUInt32("\x00\x00\x00\x00", Endian::LITTLE_ENDIAN));
        $this->assertEquals(1, Types::parseUInt32("\x00\x00\x01\x00", Endian::LITTLE_ENDIAN));
        $this->assertEquals(2147483647, Types::parseUInt32("\xFF\x7F\xFF\xFF", Endian::LITTLE_ENDIAN));
        $this->assertEquals(2147483648, Types::parseUInt32("\x00\x80\x00\x00", Endian::LITTLE_ENDIAN));
        $this->assertEquals(4294967295, Types::parseUInt32("\xFF\xFF\xFF\xFF", Endian::LITTLE_ENDIAN));
        $this->assertEquals(133124, Types::parseUInt32("\x02\x00\x04\x08", Endian::LITTLE_ENDIAN));
        $this->assertEquals(565, Types::parseUInt32("\x00\x00\x35\x02", Endian::LITTLE_ENDIAN));

        if (PHP_INT_SIZE === 8) {
            $this->assertTrue(is_int(Types::parseUInt32("\xFF\xFF\xFF\xFF", Endian::LITTLE_ENDIAN)));
            $this->assertTrue(is_int(Types::parseUInt32("\x00\x00\x01\x00", Endian::LITTLE_ENDIAN)));
        } else {
            $this->assertTrue(is_float(Types::parseUInt32("\xFF\xFF\xFF\xFF", Endian::LITTLE_ENDIAN))); // is converted to float to hold this big value

            $this->assertTrue(is_int(Types::parseUInt32("\xFF\x7F\xFF\xFF", Endian::LITTLE_ENDIAN)));
            $this->assertTrue(is_int(Types::parseUInt32("\x00\x00\x01\x00", Endian::LITTLE_ENDIAN)));
        }
    }

    public function testShouldParseInt32FromDoubleWordBigEndianLowWordFirst()
    {
        $this->assertTrue(is_int(Types::parseInt32("\xFF\xFF\x7F\xFF", Endian::BIG_ENDIAN_LOW_WORD_FIRST)));
        $this->assertEquals(0, Types::parseInt32("\x00\x00\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(1, Types::parseInt32("\x00\x01\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(-1, Types::parseInt32("\xFF\xFF\xFF\xFF", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(-2147483648, Types::parseInt32("\x00\x00\x80\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(2147483647, Types::parseInt32("\xFF\xFF\x7F\xFF", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(133124, Types::parseInt32("\x08\x04\x00\x02", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(67305985, Types::parseInt32("\x02\x01\x04\x03", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
    }

    public function testShouldParseInt32FromDoubleWordBigEndian()
    {
        $this->assertTrue(is_int(Types::parseInt32("\x7F\xFF\xFF\xFF", Endian::BIG_ENDIAN)));
        $this->assertEquals(0, Types::parseInt32("\x00\x00\x00\x00", Endian::BIG_ENDIAN));
        $this->assertEquals(1, Types::parseInt32("\x00\x00\x00\x01", Endian::BIG_ENDIAN));
        $this->assertEquals(-1, Types::parseInt32("\xFF\xFF\xFF\xFF", Endian::BIG_ENDIAN));
        $this->assertEquals(-2147483648, Types::parseInt32("\x80\x00\x00\x00", Endian::BIG_ENDIAN));
        $this->assertEquals(2147483647, Types::parseInt32("\x7F\xFF\xFF\xFF", Endian::BIG_ENDIAN));
        $this->assertEquals(133124, Types::parseInt32("\x00\x02\x08\x04", Endian::BIG_ENDIAN));
        $this->assertEquals(67305985, Types::parseInt32("\x04\x03\x02\x01", Endian::BIG_ENDIAN));
    }

    public function testShouldParseInt32FromDoubleWordLittleEndian()
    {
        $this->assertTrue(is_int(Types::parseInt32("\xFF\x7F\xFF\xFF", Endian::LITTLE_ENDIAN)));
        $this->assertEquals(0, Types::parseInt32("\x00\x00\x00\x00", Endian::LITTLE_ENDIAN));
        $this->assertEquals(1, Types::parseInt32("\x00\x00\x01\x00", Endian::LITTLE_ENDIAN));
        $this->assertEquals(-1, Types::parseInt32("\xFF\xFF\xFF\xFF", Endian::LITTLE_ENDIAN));
        $this->assertEquals(-2147483648, Types::parseInt32("\x00\x80\x00\x00", Endian::LITTLE_ENDIAN));
        $this->assertEquals(2147483647, Types::parseInt32("\xFF\x7F\xFF\xFF", Endian::LITTLE_ENDIAN));
        $this->assertEquals(133124, Types::parseInt32("\x02\x00\x04\x08", Endian::LITTLE_ENDIAN));
        $this->assertEquals(67305985, Types::parseInt32("\x03\x04\x01\x02", Endian::LITTLE_ENDIAN));
    }

    public function testShouldParseUInt64FromQuadWordBigEndianLowWordFirst()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('64-bit format codes are not available for 32-bit versions of PHP');
        }

        $this->assertEquals(0, Types::parseUInt64("\x00\x00\x00\x00\x00\x00\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(1, Types::parseUInt64("\x00\x01\x00\x00\x00\x00\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(65536, Types::parseUInt64("\x00\x00\x00\x01\x00\x00\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(2147483647, Types::parseUInt64("\xFF\xFF\x7F\xFF\x00\x00\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(2147483648, Types::parseUInt64("\x00\x00\x80\x00\x00\x00\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));

        $this->assertEquals(0x0708050603040102, Types::parseUInt64("\x01\x02\x03\x04\x05\x06\x07\x08", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
    }

    public function testShouldParseUInt64FromQuadWordBigEndian()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('64-bit format codes are not available for 32-bit versions of PHP');
        }

        $this->assertEquals(0, Types::parseUInt64("\x00\x00\x00\x00\x00\x00\x00\x00", Endian::BIG_ENDIAN));
        $this->assertEquals(1, Types::parseUInt64("\x00\x00\x00\x00\x00\x00\x00\x01", Endian::BIG_ENDIAN));
        $this->assertEquals(65536, Types::parseUInt64("\x00\x00\x00\x00\x00\x01\x00\x00", Endian::BIG_ENDIAN));
        $this->assertEquals(2147483647, Types::parseUInt64("\x00\x00\x00\x00\x7F\xFF\xFF\xFF", Endian::BIG_ENDIAN));
        $this->assertEquals(2147483648, Types::parseUInt64("\x00\x00\x00\x00\x80\x00\x00\x00", Endian::BIG_ENDIAN));

        $this->assertEquals(72623859790382856, Types::parseUInt64("\x01\x02\x03\x04\x05\x06\x07\x08", Endian::BIG_ENDIAN));
        $this->assertEquals(0x0102030405060708, Types::parseUInt64("\x01\x02\x03\x04\x05\x06\x07\x08", Endian::BIG_ENDIAN));
    }

    public function testShouldParseUInt64FromQuadWordLittleEndian()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('64-bit format codes are not available for 32-bit versions of PHP');
        }

        $this->assertEquals(0, Types::parseUInt64("\x00\x00\x00\x00\x00\x00\x00\x00", Endian::LITTLE_ENDIAN));
        $this->assertEquals(1, Types::parseUInt64("\x01\x00\x00\x00\x00\x00\x00\x00", Endian::LITTLE_ENDIAN));
        $this->assertEquals(65536, Types::parseUInt64("\x00\x00\x01\x00\x00\x00\x00\x00", Endian::LITTLE_ENDIAN));
        $this->assertEquals(2147483647, Types::parseUInt64("\xFF\xFF\xFF\x7F\x00\x00\x00\x00", Endian::LITTLE_ENDIAN));
        $this->assertEquals(2147483648, Types::parseUInt64("\x00\x00\x00\x80\x00\x00\x00\x00", Endian::LITTLE_ENDIAN));

        $this->assertEquals(72623859790382856, Types::parseUInt64("\x08\x07\x06\x05\x04\x03\x02\x01", Endian::LITTLE_ENDIAN));
        $this->assertEquals(0x0102030405060708, Types::parseUInt64("\x08\x07\x06\x05\x04\x03\x02\x01", Endian::LITTLE_ENDIAN));
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\OverflowException
     * @expectedExceptionMessage  64-bit PHP supports only up to 63-bit signed integers. Current input has 64th bit set and overflows
     */
    public function testShouldFailToParseUInt64FromQuadWord()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('64-bit format codes are not available for 32-bit versions of PHP');
        }
        Types::parseUInt64("\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF", Endian::BIG_ENDIAN);
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ParseException
     * @expectedExceptionMessage  binaryData must be 8 bytes in length
     */
    public function testShouldFailToParseUInt64FromTooShortString()
    {
        Types::parseUInt64("\xFF\xFF\xFF\xFF\xFF\xFF\xFF", Endian::BIG_ENDIAN);
    }

    public function testShouldParseInt64FromQuadWordBigEndian()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('64-bit format codes are not available for 32-bit versions of PHP');
        }

        $this->assertEquals(-1, Types::parseInt64("\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF", Endian::BIG_ENDIAN));
        $this->assertEquals(0, Types::parseInt64("\x00\x00\x00\x00\x00\x00\x00\x00", Endian::BIG_ENDIAN));
        $this->assertEquals(1, Types::parseInt64("\x00\x00\x00\x00\x00\x00\x00\x01", Endian::BIG_ENDIAN));
        $this->assertEquals(-9223372036854775808, Types::parseInt64("\x80\x00\x00\x00\x00\x00\x00\x00", Endian::BIG_ENDIAN));
        $this->assertEquals(9223372036854775807, Types::parseInt64("\x7F\xFF\xFF\xFF\xFF\xFF\xFF\xFF", Endian::BIG_ENDIAN));
    }

    public function testShouldParseInt64FromQuadWordLittleEndian()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('64-bit format codes are not available for 32-bit versions of PHP');
        }

        $this->assertEquals(-1, Types::parseInt64("\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF", Endian::LITTLE_ENDIAN));
        $this->assertEquals(0, Types::parseInt64("\x00\x00\x00\x00\x00\x00\x00\x00", Endian::LITTLE_ENDIAN));
        $this->assertEquals(1, Types::parseInt64("\x01\x00\x00\x00\x00\x00\x00\x00", Endian::LITTLE_ENDIAN));
        $this->assertEquals(-9223372036854775808, Types::parseInt64("\x00\x00\x00\x00\x00\x00\x00\x80", Endian::LITTLE_ENDIAN));
        $this->assertEquals(9223372036854775807, Types::parseInt64("\xFF\xFF\xFF\xFF\xFF\xFF\xFF\x7F", Endian::LITTLE_ENDIAN));
    }

    public function testShouldParseInt64FromQuadWordBigEndianLowWordFirst()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('64-bit format codes are not available for 32-bit versions of PHP');
        }

        $this->assertEquals(-1, Types::parseInt64("\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(0, Types::parseInt64("\x00\x00\x00\x00\x00\x00\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(1, Types::parseInt64("\x00\x01\x00\x00\x00\x00\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(65536, Types::parseInt64("\x00\x00\x00\x01\x00\x00\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(2147483647, Types::parseInt64("\xFF\xFF\x7F\xFF\x00\x00\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(2147483648, Types::parseInt64("\x00\x00\x80\x00\x00\x00\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));

        $this->assertEquals(0x0708050603040102, Types::parseInt64("\x01\x02\x03\x04\x05\x06\x07\x08", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(-9223372036854775808, Types::parseInt64("\x00\x00\x00\x00\x00\x00\x80\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals(9223372036854775807, Types::parseInt64("\xFF\xFF\xFF\xFF\xFF\xFF\x7F\xFF", Endian::BIG_ENDIAN_LOW_WORD_FIRST));
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ParseException
     * @expectedExceptionMessage  binaryData must be 8 bytes in length
     */
    public function testShouldFailToParseInt64FromTooShortString()
    {
        Types::parseInt64("\xFF\xFF\xFF\xFF\xFF\xFF\xFF", Endian::BIG_ENDIAN);
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\OverflowException
     * @expectedExceptionMessage  64-bit PHP supports only up to 63-bit signed integers. Current input has 64th bit set and overflows
     */
    public function testShouldFailToParseUInt64FromQuadWord2()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('64-bit format codes are not available for 32-bit versions of PHP');
        }
        Types::parseUInt64("\x00\x01\x00\x00\x00\x00\x80\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST);
    }

    public function testShouldEncodeToBinaryByte()
    {
        $this->assertEquals("\x01", Types::toByte(1));
        $this->assertEquals("\xFF", Types::toByte(65535));
    }

    public function testShouldParseByteFromBinaryData()
    {
        $this->assertEquals(1, Types::parseByte("\x01"));
        $this->assertEquals(255, Types::parseByte("\xFF"));
    }

    public function testShouldConvertBooleanArrayToByteArrayOneByte()
    {
        $this->assertEquals([85], Types::booleanArrayToByteArray([1, 0, 1, 0, 1, 0, 1]));
        $this->assertEquals([85], Types::booleanArrayToByteArray([1, 0, 1, 0, 1, 0, 1, 0]));
    }

    public function testShouldConvertBooleanArrayToByteArrayMultiByte()
    {
        $this->assertEquals([85, 0, 9], Types::booleanArrayToByteArray([
            1, 0, 1, 0, 1, 0, 1, 0,
            0, 0, 0, 0, 0, 0, 0, 0,
            1, 0, 0, 1
        ]));
    }

    public function testShouldConvertArrayToBytes()
    {
        $this->assertEquals("\x55\x0\x09", Types::byteArrayToByte([85, 0, 9]));
    }

    public function testShouldConvertBinaryStringToBooleanArray()
    {
        // bit are set from least significant (right) to most significant (left) so it is reversed
        $this->assertEquals([
            1, 0, 1, 0, 1, 0, 1, 0, // hex: 55 = bin: 0101 0101 -> reverse for user input: 1010 1010
            1, 0, 0, 1, 0, 0, 0, 0  // hex: 09 = bin: 0000 1001 -> reverse for user input: 1001 0000
        ], Types::binaryStringToBooleanArray("\x55\x09"));
    }

    public function testShouldSeeIfBitIsSet()
    {
        $this->assertTrue(Types::isBitSet(bindec('011111111'), 0));
        $this->assertTrue(Types::isBitSet(bindec('011111111'), 1));
        $this->assertTrue(Types::isBitSet(bindec('011111111'), 7));

        $this->assertTrue(Types::isBitSet("\xFF\x05", 0));
        $this->assertTrue(Types::isBitSet("\xFF\x05", 2));
        $this->assertTrue(Types::isBitSet("\x05\x01\xFF\x05", 2));
        $this->assertTrue(Types::isBitSet("\x05\x01\x00\x05", 16));
        $this->assertTrue(Types::isBitSet("\x05\x01\x00\x05", 26));
    }

    public function testShouldSeeIfBitIsNotSet()
    {
        $this->assertFalse(Types::isBitSet(null, 12));
        $this->assertFalse(Types::isBitSet(bindec('000110011'), 2));
        $this->assertFalse(Types::isBitSet(bindec('011111111'), 8));
        $this->assertFalse(Types::isBitSet("\x05\x01\xFF\x05", 1));
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessageRegExp  /On .*bit PHP bit shifting more than .* bit is not possible as int size is .* bytes/
     */
    public function testShouldExceptionWhenBitToHighNumber()
    {
        if (PHP_INT_SIZE === 4) {
            Types::isBitSet(1000, 32);
        } else {
            Types::isBitSet(1000, 64);
        }
    }

    public function testShouldConvertFloatToRealWithBigEndianLowWordFirst()
    {
        $this->assertEquals("\xcc\xcd\x3f\xec", Types::toReal(1.85, Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals("\xaa\xab\x3f\x2a", Types::toReal(0.66666666666, Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals("\x00\x00\x00\x00", Types::toReal(null, Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals("\x00\x00\x00\x00", Types::toReal(0, Endian::BIG_ENDIAN_LOW_WORD_FIRST));
    }

    public function testShouldConvertFloatToRealWithBigEndian()
    {
        $this->assertEquals("\x3f\xec\xcc\xcd", Types::toReal(1.85, Endian::BIG_ENDIAN));
        $this->assertEquals("\x3f\x2a\xaa\xab", Types::toReal(0.66666666666, Endian::BIG_ENDIAN));
        $this->assertEquals("\x00\x00\x00\x00", Types::toReal(null, Endian::BIG_ENDIAN));
        $this->assertEquals("\x00\x00\x00\x00", Types::toReal(0, Endian::BIG_ENDIAN));
    }

    public function testShouldConvertFloatToRealWithLittleEndian()
    {
        $this->assertEquals("\xec\x3f\xcd\xcc", Types::toReal(1.85, Endian::LITTLE_ENDIAN));
        $this->assertEquals("\x2a\x3f\xab\xaa", Types::toReal(0.66666666666, Endian::LITTLE_ENDIAN));
        $this->assertEquals("\x00\x00\x00\x00", Types::toReal(null, Endian::LITTLE_ENDIAN));
        $this->assertEquals("\x00\x00\x00\x00", Types::toReal(0, Endian::LITTLE_ENDIAN));
    }

    public function testShouldParseFloatAsBigEndianLowWordFirst()
    {
        $float = Types::parseFloat("\xcc\xcd\x3f\xec", Endian::BIG_ENDIAN_LOW_WORD_FIRST);

        $this->assertTrue(is_float($float));
        $this->assertEquals(1.85, $float, null, 0.0000001);

        $this->assertEquals(0.66666666666, Types::parseFloat("\xaa\xab\x3f\x2a", Endian::BIG_ENDIAN_LOW_WORD_FIRST), null, 0.0000001);
        $this->assertEquals(0, Types::parseFloat("\x00\x00\x00\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST), null, 0.0000001);
    }

    public function testShouldParseFloatAsBigEndian()
    {
        $float = Types::parseFloat("\x3f\xec\xcc\xcd", Endian::BIG_ENDIAN);

        $this->assertTrue(is_float($float));
        $this->assertEquals(1.85, $float, null, 0.0000001);

        $this->assertEquals(0.66666666666, Types::parseFloat("\x3f\x2a\xaa\xab", Endian::BIG_ENDIAN), null, 0.0000001);
        $this->assertEquals(0, Types::parseFloat("\x00\x00\x00\x00", Endian::BIG_ENDIAN), null, 0.0000001);
    }

    public function testShouldParseFloatAsLittleEndian()
    {
        $float = Types::parseFloat("\xcd\xcc\xec\x3f", Endian::LITTLE_ENDIAN);

        $this->assertTrue(is_float($float));
        $this->assertEquals(1.85, $float, null, 0.0000001);

        $this->assertEquals(0.66666666666, Types::parseFloat("\xab\xaa\x2a\x3f", Endian::LITTLE_ENDIAN), null, 0.0000001);
        $this->assertEquals(0, Types::parseFloat("\x00\x00\x00\x00", Endian::LITTLE_ENDIAN), null, 0.0000001);
    }

    public function testShouldParseStringFromRegisterAsLittleEndian()
    {
        // null terminated data
        $string = Types::parseAsciiStringFromRegister("\x53\xF8\x72\x65\x6E\x00", 0, Endian::LITTLE_ENDIAN);
        $this->assertEquals('Søren', $string);

        $string = Types::parseAsciiStringFromRegister("\x53\xF8\x72\x65\x6E", 0, Endian::LITTLE_ENDIAN);
        $this->assertEquals('Søren', $string);

        // parse substring from data
        $string = Types::parseAsciiStringFromRegister("\x53\xF8\x72\x65\x6E\x00", 3, Endian::LITTLE_ENDIAN);
        $this->assertEquals('Sør', $string);
    }

    public function testShouldParseStringFromRegisterAsBigEndian()
    {
        // null terminated data
        $string = Types::parseAsciiStringFromRegister("\x00\x6E", 10, Endian::BIG_ENDIAN);
        $this->assertEquals('n', $string);

        // null terminated data
        $string = Types::parseAsciiStringFromRegister("\xF8\x53\x65\x72\x00\x6E", 0, Endian::BIG_ENDIAN);
        $this->assertEquals('Søren', $string);

        // odd number of bytes in data
        $string = Types::parseAsciiStringFromRegister("\xF8\x53\x65\x72\x00", 0, Endian::BIG_ENDIAN);
        $this->assertEquals('Søre', $string);

        // parse substring from data
        $string = Types::parseAsciiStringFromRegister("\xF8\x53\x65\x72\x00\x6E", 3, Endian::BIG_ENDIAN);
        $this->assertEquals('Sør', $string);
    }

    /**
     * @dataProvider toInt16Provider
     */
    public function testShouldEncodeToBinaryInt16(string $expectedBinaryString, int $integer, int $endian, $expectedException = null)
    {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }
        $this->assertEquals($expectedBinaryString, Types::toInt16($integer, $endian));
    }

    public function toInt16Provider()
    {
        return [
            'BigEndian: toInt16 1' => ["\x00\x01", 1, Endian::BIG_ENDIAN],
            'BigEndian: toInt16 -1' => ["\xFF\xFF", -1, Endian::BIG_ENDIAN],
            'BigEndian: toInt16 32767 (max int16)' => ["\x7F\xFF", 32767, Endian::BIG_ENDIAN],
            'BigEndian: toInt16 -32768 (min int16)' => ["\x80\x00", -32768, Endian::BIG_ENDIAN],

            'BigEndian: toInt16 32768 (overflow)' => ['', 32768, Endian::BIG_ENDIAN, \ModbusTcpClient\Exception\OverflowException::class],
            'BigEndian: toInt16 -32769 (underflow)' => ['', -32769, Endian::BIG_ENDIAN, \ModbusTcpClient\Exception\OverflowException::class],

            'LittleEndian: toInt16 1' => ["\x01\x00", 1, Endian::LITTLE_ENDIAN],
            'LittleEndian: toInt16 -1' => ["\xFF\xFF", -1, Endian::LITTLE_ENDIAN],
            'LittleEndian: toInt16 -32768 (min int16)' => ["\x00\x80", -32768, Endian::LITTLE_ENDIAN],
        ];
    }

    /**
     * @dataProvider toUint16Provider
     */
    public function testShouldEncodeToBinaryUint16(string $expectedBinaryString, int $integer, int $endian, $expectedException = null)
    {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }
        $this->assertEquals($expectedBinaryString, Types::toUint16($integer, $endian));
    }

    public function toUint16Provider()
    {
        return [
            'BigEndian: toInt16 1 (min uint16)' => ["\x00\x01", 1, Endian::BIG_ENDIAN],
            'BigEndian: toInt16 65535 (max uint16)' => ["\xFF\xFF", 65535, Endian::BIG_ENDIAN],
            'BigEndian: toInt16 32767' => ["\x7F\xFF", 32767, Endian::BIG_ENDIAN],

            'BigEndian: toInt16 65536 (overflow)' => ['', 65536, Endian::BIG_ENDIAN, \ModbusTcpClient\Exception\OverflowException::class],
            'BigEndian: toInt16 -1 (underflow)' => ['', -1, Endian::BIG_ENDIAN, \ModbusTcpClient\Exception\OverflowException::class],

            'LittleEndian: toInt16 1' => ["\x01\x00", 1, Endian::LITTLE_ENDIAN],
            'LittleEndian: toInt16 32767' => ["\xFF\x7F", 32767, Endian::LITTLE_ENDIAN],
        ];
    }

    /**
     * @dataProvider toInt32Provider
     */
    public function testShouldEncodeToBinaryInt32(string $expectedBinaryString, $integer, int $endian, $expectedException = null)
    {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }
        $this->assertEquals($expectedBinaryString, Types::toInt32($integer, $endian));
    }

    public function toInt32Provider()
    {
        return [
            'BigEndian: toInt32 1' => ["\x00\x00\x00\x01", 1, Endian::BIG_ENDIAN],
            'BigEndian: toInt32 -1' => ["\xFF\xFF\xFF\xFF", -1, Endian::BIG_ENDIAN],
            'BigEndian: toInt32 2147483647 (max int32)' => ["\x7F\xFF\xFF\xFF", 2147483647, Endian::BIG_ENDIAN],
            'BigEndian: toInt32 -2147483648 (min int32)' => ["\x80\x00\x00\x00", -2147483648, Endian::BIG_ENDIAN],

            'BigEndian: toInt32 2147483648 (overflow)' => ['', 2147483648, Endian::BIG_ENDIAN, \ModbusTcpClient\Exception\OverflowException::class],
            'BigEndian: toInt32 -2147483649 (underflow)' => ['', -2147483649, Endian::BIG_ENDIAN, \ModbusTcpClient\Exception\OverflowException::class],

            'BigEndianLowWordFirst: toInt32 1' => ["\x00\x01\x00\x00", 1, Endian::BIG_ENDIAN_LOW_WORD_FIRST],
            'BigEndianLowWordFirst: toInt32 -2147483648 (min int32)' => ["\x00\x00\x80\x00", -2147483648, Endian::BIG_ENDIAN_LOW_WORD_FIRST],

            'LittleEndian: toInt32 1' => ["\x00\x00\x01\x00", 1, Endian::LITTLE_ENDIAN],
            'LittleEndian: toInt32 2147483647 (max int32)' => ["\xFF\x7F\xFF\xFF", 2147483647, Endian::LITTLE_ENDIAN],
        ];
    }

    /**
     * @dataProvider toUint32Provider
     */
    public function testShouldEncodeToBinaryUint32(string $expectedBinaryString, $integer, int $endian, $expectedException = null)
    {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }
        $this->assertEquals($expectedBinaryString, Types::toUint32($integer, $endian));
    }

    public function toUint32Provider()
    {
        return [
            'BigEndian: toInt32 1' => ["\x00\x00\x00\x01", 1, Endian::BIG_ENDIAN],
            'BigEndian: toInt32 0 (min uint32)' => ["\x00\x00\x00\x00", 0, Endian::BIG_ENDIAN],
            'BigEndian: toInt32 4294967295 (max uint32)' => ["\xFF\xFF\xFF\xFF", 4294967295, Endian::BIG_ENDIAN],
            'BigEndian: toInt32 -2147483648' => ["\x80\x00\x00\x00", 0x80000000, Endian::BIG_ENDIAN],

            'BigEndian: toInt32 4294967296 (overflow)' => ['', 4294967296, Endian::BIG_ENDIAN, \ModbusTcpClient\Exception\OverflowException::class],
            'BigEndian: toInt32 -1 (underflow)' => ['', -1, Endian::BIG_ENDIAN, \ModbusTcpClient\Exception\OverflowException::class],

            'BigEndianLowWordFirst: toInt32 1' => ["\x00\x01\x00\x00", 1, Endian::BIG_ENDIAN_LOW_WORD_FIRST],
            'BigEndianLowWordFirst: toInt32 -2147483648' => ["\x00\x00\x80\x00", 0x80000000, Endian::BIG_ENDIAN_LOW_WORD_FIRST],

            'LittleEndian: toInt32 1' => ["\x00\x00\x01\x00", 1, Endian::LITTLE_ENDIAN],
            'LittleEndian: toInt32 0 (min uint32)' => ["\x00\x00\x00\x00", 0, Endian::LITTLE_ENDIAN],
            'LittleEndian: toInt32 4294967295 (max uint32)' => ["\xFF\xFF\xFF\xFF", 4294967295, Endian::LITTLE_ENDIAN],
            'LittleEndian: toInt32 -2147483648' => ["\x00\x80\x00\x00", 0x80000000, Endian::LITTLE_ENDIAN],
        ];
    }

    /**
     * @dataProvider toInt64Provider
     */
    public function testShouldEncodeToBinaryInt64(string $expectedBinaryString, $integer, int $endian, $skipOn32Bit = false)
    {
        if ($skipOn32Bit && PHP_INT_SIZE === 4) {
            $this->markTestSkipped('32-bit version of PHP');
        }

        $this->assertEquals($expectedBinaryString, Types::toInt64($integer, $endian));
    }

    public function toInt64Provider()
    {
        return [
            'BigEndianLowWordFirst: toInt64 1' => ["\x00\x01\x00\x00\x00\x00\x00\x00", 1, Endian::BIG_ENDIAN_LOW_WORD_FIRST],
            'BigEndianLowWordFirst: toInt64 2923517522' => ["\x56\x52\xAE\x41\x00\x00\x00\x0", 2923517522, Endian::BIG_ENDIAN_LOW_WORD_FIRST, true],
            'BigEndianLowWordFirst: toInt64 67305985' => ["\x02\x01\x04\x03\x00\x00\x00\x00", 67305985, Endian::BIG_ENDIAN_LOW_WORD_FIRST],
            'BigEndianLowWordFirst: toInt64 9223372036854775807' => ["\xFF\xFF\xFF\xFF\xFF\xFF\x7F\xFF", 9223372036854775807, Endian::BIG_ENDIAN_LOW_WORD_FIRST, true],
            'BigEndianLowWordFirst: toInt64 -9223372036854775808' => ["\x00\x00\x00\x00\x00\x00\x80\x00", -9223372036854775808, Endian::BIG_ENDIAN_LOW_WORD_FIRST, true],

            'BigEndian: toInt64 1' => ["\x00\x00\x00\x00\x00\x00\x00\x01", 1, Endian::BIG_ENDIAN],
            'BigEndian: toInt64 2923517522' => ["\x00\x00\x00\x00\xAE\x41\x56\x52", 2923517522, Endian::BIG_ENDIAN, true],
            'BigEndian: toInt64 67305985' => ["\x00\x00\x00\x00\x04\x03\x02\x01", 67305985, Endian::BIG_ENDIAN],
            'BigEndian: toInt64 9223372036854775807' => ["\x7F\xFF\xFF\xFF\xFF\xFF\xFF\xFF", 9223372036854775807, Endian::BIG_ENDIAN, true],
            'BigEndian: toInt64 -9223372036854775808' => ["\x80\x00\x00\x00\x00\x00\x00\x00", -9223372036854775808, Endian::BIG_ENDIAN, true],

            'LittleEndian: toInt64 -9223372036854775808' => ["\x00\x80\x00\x00\x00\x00\x00\x00", -9223372036854775808, Endian::LITTLE_ENDIAN, true],
        ];
    }

    /**
     * @dataProvider toUint64Provider
     */
    public function testShouldEncodeToBinaryUint64(string $expectedBinaryString, $integer, int $endian, $expectedException = null, $skipOn32Bit = false)
    {
        if ($skipOn32Bit && PHP_INT_SIZE === 4) {
            $this->markTestSkipped('32-bit version of PHP');
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }
        $this->assertEquals($expectedBinaryString, Types::toUint64($integer, $endian));
    }

    public function toUint64Provider()
    {
        return [
            'BigEndianLowWordFirst: toInt64 1' => ["\x00\x01\x00\x00\x00\x00\x00\x00", 1, Endian::BIG_ENDIAN_LOW_WORD_FIRST],
            'BigEndianLowWordFirst: toInt64 2923517522' => ["\x56\x52\xAE\x41\x00\x00\x00\x0", 2923517522, Endian::BIG_ENDIAN_LOW_WORD_FIRST, null, true],
            'BigEndianLowWordFirst: toInt64 67305985' => ["\x02\x01\x04\x03\x00\x00\x00\x00", 67305985, Endian::BIG_ENDIAN_LOW_WORD_FIRST],
            'BigEndianLowWordFirst: toInt64 9223372036854775807' => ["\xFF\xFF\xFF\xFF\xFF\xFF\x7F\xFF", 9223372036854775807, Endian::BIG_ENDIAN_LOW_WORD_FIRST, null, true],

            'BigEndianLowWordFirst: toInt64 -1 (underflow)' => ['', -1, Endian::BIG_ENDIAN_LOW_WORD_FIRST, \ModbusTcpClient\Exception\OverflowException::class],

            'BigEndian: toInt64 1' => ["\x00\x00\x00\x00\x00\x00\x00\x01", 1, Endian::BIG_ENDIAN],
            'BigEndian: toInt64 2923517522' => ["\x00\x00\x00\x00\xAE\x41\x56\x52", 2923517522, Endian::BIG_ENDIAN, null, true],
            'BigEndian: toInt64 67305985' => ["\x00\x00\x00\x00\x04\x03\x02\x01", 67305985, Endian::BIG_ENDIAN],
            'BigEndian: toInt64 9223372036854775807' => ["\x7F\xFF\xFF\xFF\xFF\xFF\xFF\xFF", 9223372036854775807, Endian::BIG_ENDIAN, null, true],
            'BigEndian: toInt64 -1 (underflow)' => ['', -1, Endian::BIG_ENDIAN, \ModbusTcpClient\Exception\OverflowException::class],

            'LittleEndian: toInt64 9223372036854775807' => ["\xFF\x7F\xFF\xFF\xFF\xFF\xFF\xFF", 9223372036854775807, Endian::LITTLE_ENDIAN, null, true],
        ];
    }

    /**
     * @dataProvider toStringProvider
     */
    public function testShouldEncodeToBinaryString(string $expectedBinaryString, string $string = null, int $registersCount = null, int $endian, $expectedException = null)
    {
        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }
        $this->assertEquals($expectedBinaryString, Types::toString($string, $registersCount, 'cp1252', $endian));
    }

    public function toStringProvider()
    {
        return [
            'null: toString "\x00\x00\x00\x00"' => ["\x00\x00\x00\x00", null, 2, Endian::BIG_ENDIAN],
            'BigEndian: toString "Søren\x00"' => ["\xF8\x53\x65\x72\x00\x6E", 'Søren', 3, Endian::BIG_ENDIAN],
            'BigEndian: toString "Søre\x00\x00"' => ["\xF8\x53\x65\x72\x00\x00", 'Søre', 3, Endian::BIG_ENDIAN],
            'BigEndian: toString "Sør\x00"' => ["\xF8\x53\x00\x72", 'Søren', 2, Endian::BIG_ENDIAN],

            'BigEndianLowWordFirst: toString "Søren\x00"' => ["\x00\x6E\x65\x72\xF8\x53", 'Søren', 3, Endian::BIG_ENDIAN_LOW_WORD_FIRST],

            'LittleEndian: toString "Sør\x00"' => ["\x53\xF8\x72\x00", 'Søren', 2, Endian::LITTLE_ENDIAN],
        ];
    }

}