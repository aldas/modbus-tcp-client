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

}