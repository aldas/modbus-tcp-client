<?php
namespace Tests\Utils;


use ModbusTcpClient\Utils\Types;
use PHPUnit\Framework\TestCase;

class TypesTest extends TestCase
{
    public function testShouldParseUint16FromBinaryData()
    {
        $this->assertEquals(1, Types::parseUInt16BE("\x00\x01"));
        $this->assertEquals(65535, Types::parseUInt16BE("\xFF\xFF"));
    }

    public function testShouldEncodeToBinaryUint16()
    {
        $this->assertEquals("\x00\x01", Types::toUInt16BE(1));
        $this->assertEquals("\xFF\xFF", Types::toUInt16BE(65535));
    }

    public function testShouldEncodeToBinaryInt32()
    {
        $this->assertEquals("\x00\x01\x00\x00", Types::toInt32BE(1));
        $this->assertEquals("\x56\x52\xAE\x41", Types::toInt32BE(2923517522));
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

}