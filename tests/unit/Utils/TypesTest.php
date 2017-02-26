<?php
namespace Tests\Utils;


use ModbusTcpClient\Utils\Types;
use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{
    public function testShouldParseUint16FromWord()
    {
        $this->assertEquals(1, Types::parseUInt16BE("\x00\x01"));
        $this->assertEquals(32767, Types::parseUInt16BE("\x7F\xFF"));
        $this->assertEquals(32768, Types::parseUInt16BE("\x80\x00"));
        $this->assertEquals(65535, Types::parseUInt16BE("\xFF\xFF"));
    }

    public function testShouldParseInt16FromWord()
    {
        $this->assertEquals(0, Types::parseInt16BE("\x00\x00"));
        $this->assertEquals(1, Types::parseInt16BE("\x00\x01"));
        $this->assertEquals(-1, Types::parseInt16BE("\xFF\xFF"));
        $this->assertEquals(-32768, Types::parseInt16BE("\x80\x00"));
        $this->assertEquals(32767, Types::parseInt16BE("\x7F\xFF"));
    }

    public function testShouldParseUInt32FromDoubleWord()
    {
        $this->assertEquals(0, Types::parseUInt32BE("\x00\x00\x00\x00"));
        $this->assertEquals(1, Types::parseUInt32BE("\x00\x01\x00\x00"));
        $this->assertEquals(2147483647, Types::parseUInt32BE("\xFF\xFF\x7F\xFF"));
        $this->assertEquals(2147483648, Types::parseUInt32BE("\x00\x00\x80\x00"));
        $this->assertEquals(4294967295, Types::parseUInt32BE("\xFF\xFF\xFF\xFF"));
        $this->assertEquals(133124, Types::parseUInt32BE("\x08\x04\x00\x02"));
        $this->assertEquals(565, Types::parseUInt32BE("\x02\x35\x00\x00"));

        if (PHP_INT_SIZE === 8) {
            $this->assertTrue(is_int(Types::parseUInt32BE("\xFF\xFF\xFF\xFF")));
            $this->assertTrue(is_int(Types::parseUInt32BE("\x00\x01\x00\x00")));
        } else {
            $this->assertTrue(is_float(Types::parseUInt32BE("\xFF\xFF\xFF\xFF"))); // is converted to float to hold this big value

            $this->assertTrue(is_int(Types::parseUInt32BE("\xFF\xFF\x7F\xFF")));
            $this->assertTrue(is_int(Types::parseUInt32BE("\x00\x01\x00\x00")));
        }
    }

    public function testShouldParseInt32FromDoubleWord()
    {
        $this->assertTrue(is_int(Types::parseInt32BE("\xFF\xFF\x7F\xFF")));
        $this->assertEquals(0, Types::parseInt32BE("\x00\x00\x00\x00"));
        $this->assertEquals(1, Types::parseInt32BE("\x00\x01\x00\x00"));
        $this->assertEquals(-1, Types::parseInt32BE("\xFF\xFF\xFF\xFF"));
        $this->assertEquals(-2147483648, Types::parseInt32BE("\x00\x00\x80\x00"));
        $this->assertEquals(2147483647, Types::parseInt32BE("\xFF\xFF\x7F\xFF"));
        $this->assertEquals(133124, Types::parseInt32BE("\x08\x04\x00\x02"));
        $this->assertEquals(67305985, Types::parseInt32BE("\x02\x01\x04\x03"));
    }

    public function testShouldParseUInt64FromQuadWord()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('64-bit format codes are not available for 32-bit versions of PHP');
        }

        $this->assertEquals(0, Types::parseUInt64("\x00\x00\x00\x00\x00\x00\x00\x00"));
        $this->assertEquals(1, Types::parseUInt64("\x00\x00\x00\x00\x00\x01\x00\x00"));
        $this->assertEquals(65536, Types::parseUInt64("\x00\x00\x00\x00\x00\x00\x00\x01"));
        $this->assertEquals(2147483647, Types::parseUInt64("\x00\x00\x00\x00\xFF\xFF\x7F\xFF"));
        $this->assertEquals(2147483648, Types::parseUInt64("\x00\x00\x00\x00\x00\x00\x80\x00"));

        $this->assertEquals('72,057,594,180,534,272', number_format(Types::parseUInt64("\x00\x00\x01\x00\x00\x04\x08\x80")));
        $this->assertEquals('9,223,372,036,854,775,808', number_format(Types::parseUInt64("\x00\x00\x80\x00\x00\x00\x00\x00")));
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
        Types::parseUInt64("\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF");
    }

    /**
     * @expectedException \LengthException
     * @expectedExceptionMessage  binaryData must be 8 bytes in length
     */
    public function testShouldFailToParseUInt64FromTooShortString()
    {
        Types::parseUInt64("\xFF\xFF\xFF\xFF\xFF\xFF\xFF");
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
        Types::parseUInt64("\x00\x00\x80\x00\x00\x01\x00\x00");
    }

    public function testShouldEncodeToBinaryint16()
    {
        $this->assertEquals("\x00\x01", Types::toInt16BE(1));
        $this->assertEquals("\xFF\xFF", Types::toInt16BE(65535));
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

    public function testShouldEncodeToBinaryInt32()
    {
        $this->assertEquals("\x00\x01\x00\x00", Types::toInt32BE(1));
        $this->assertEquals("\x56\x52\xAE\x41", Types::toInt32BE(2923517522));
        $this->assertEquals("\x02\x01\x04\x3", Types::toInt32BE(67305985));
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

    public function testShouldConvertFloatToReal()
    {
        $this->assertEquals("\xcc\xcd\x3f\xec", Types::toReal(1.85));
        $this->assertEquals("\xaa\xab\x3f\x2a", Types::toReal(0.66666666666));
        $this->assertEquals("\x00\x00\x00\x00", Types::toReal(null));
        $this->assertEquals("\x00\x00\x00\x00", Types::toReal(0));
    }

    public function testShouldParseFloat()
    {
        $float = Types::parseFloat("\xcc\xcd\x3f\xec");

        $this->assertTrue(is_float($float));
        $this->assertEquals(1.85, $float, null, 0.0000001);

        $this->assertEquals(0.66666666666, Types::parseFloat("\xaa\xab\x3f\x2a"), null, 0.0000001);
        $this->assertEquals(0, Types::parseFloat("\x00\x00\x00\x00"), null, 0.0000001);
    }

}