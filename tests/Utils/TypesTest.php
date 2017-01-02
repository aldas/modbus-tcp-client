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

}