<?php

namespace Tests\Utils;


use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class EndianTest extends TestCase
{
    public function testGetCurrentEndianessReturnsBELWFasDefault()
    {
        $this->assertEquals(Endian::BIG_ENDIAN_LOW_WORD_FIRST, Endian::getCurrentEndianness());
    }

    public function testGetCurrentEndianessReturnsBEWhenSetAsDefault()
    {
        Endian::$defaultEndian = Endian::BIG_ENDIAN;
        $this->assertEquals(Endian::BIG_ENDIAN, Endian::getCurrentEndianness());
    }

    public function testGetCurrentEndianessReturnsBEWhenPassedAsArgument()
    {
        $this->assertEquals(Endian::BIG_ENDIAN, Endian::getCurrentEndianness(Endian::BIG_ENDIAN));
    }

}