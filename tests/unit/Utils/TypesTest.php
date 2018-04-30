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
     * @expectedException \RangeException
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
     * @expectedException \LengthException
     * @expectedExceptionMessage  binaryData must be 8 bytes in length
     */
    public function testShouldFailToParseUInt64FromTooShortString()
    {
        Types::parseUInt64("\xFF\xFF\xFF\xFF\xFF\xFF\xFF", Endian::BIG_ENDIAN);
    }

    /**
     * @expectedException \RangeException
     * @expectedExceptionMessage  64-bit PHP supports only up to 63-bit signed integers. Current input has 64th bit set and overflows
     */
    public function testShouldFailToParseUInt64FromQuadWord2()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('64-bit format codes are not available for 32-bit versions of PHP');
        }
        Types::parseUInt64("\x00\x01\x00\x00\x00\x00\x80\x00", Endian::BIG_ENDIAN_LOW_WORD_FIRST);
    }

    public function testShouldEncodeToBinaryint16WithBigEndian()
    {
        $this->assertEquals("\x00\x01", Types::toInt16(1, Endian::BIG_ENDIAN));
        $this->assertEquals("\xFF\xFF", Types::toInt16(65535, Endian::BIG_ENDIAN));
    }

    public function testShouldEncodeToBinaryint16WithLittleEndian()
    {
        $this->assertEquals("\x01\x00", Types::toInt16(1, Endian::LITTLE_ENDIAN));
        $this->assertEquals("\xFF\xFF", Types::toInt16(65535, Endian::LITTLE_ENDIAN));
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

    public function testShouldEncodeToBinaryInt32WithBigEndianLowWordFirst()
    {
        $this->assertEquals("\x00\x01\x00\x00", Types::toInt32(1, Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals("\x56\x52\xAE\x41", Types::toInt32(2923517522, Endian::BIG_ENDIAN_LOW_WORD_FIRST));
        $this->assertEquals("\x02\x01\x04\x3", Types::toInt32(67305985, Endian::BIG_ENDIAN_LOW_WORD_FIRST));
    }

    public function testShouldEncodeToBinaryInt32WithBigEndian()
    {
        $this->assertEquals("\x00\x00\x00\x01", Types::toInt32(1, Endian::BIG_ENDIAN));
        $this->assertEquals("\xAE\x41\x56\x52", Types::toInt32(2923517522, Endian::BIG_ENDIAN));
        $this->assertEquals("\x04\x03\x02\x01", Types::toInt32(67305985, Endian::BIG_ENDIAN));
    }

    public function testShouldEncodeToBinaryInt32WithLittleEndian()
    {
        $this->assertEquals("\x00\x00\x01\x00", Types::toInt32(1, Endian::LITTLE_ENDIAN));
        $this->assertEquals("\x41\xAE\x52\x56", Types::toInt32(2923517522, Endian::LITTLE_ENDIAN));
        $this->assertEquals("\x03\x04\x01\x02", Types::toInt32(67305985, Endian::LITTLE_ENDIAN));
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
     * @expectedException \InvalidArgumentException
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
        $string = Types::parseAsciiStringFromRegister("\xF8\x53\x65\x72\x00\x6E", 0, Endian::BIG_ENDIAN);
        $this->assertEquals('Søren', $string);

        // odd number of bytes in data
        $string = Types::parseAsciiStringFromRegister("\xF8\x53\x65\x72\x00", 0, Endian::BIG_ENDIAN);
        $this->assertEquals('Søre', $string);

        // parse substring from data
        $string = Types::parseAsciiStringFromRegister("\xF8\x53\x65\x72\x00\x6E", 3, Endian::BIG_ENDIAN);
        $this->assertEquals('Sør', $string);
    }

}