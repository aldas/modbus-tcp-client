<?php

namespace Tests\Packet;

use ModbusTcpClient\Packet\QuadWord;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class QuadWordTest extends TestCase
{
    public function testShouldConstructFromShorterData()
    {
        $dWord = new QuadWord("\x7F\xFF");

        $this->assertEquals("\x00\x00\x00\x00\x00\x00\x7F\xFF", $dWord->getData());
    }

    /**
     * @expectedException \ModbusTcpClient\ModbusException
     * @expectedExceptionMessage QuadWord can only be constructed from 1 to 8 bytes. Currently 9 bytes was given!
     */
    public function testShouldNotConstructFromLongerData()
    {
        new QuadWord("\x01\x02\x03\x04\x05\x06\x07\x08\x09");
    }

    public function testShouldGetUInt64()
    {
        $quadWord = new QuadWord("\xFF\xFF\x7F\xFF\x00\x00\x00\x00");

        $this->assertEquals(2147483647, $quadWord->getUInt64(Endian::BIG_ENDIAN_LOW_WORD_FIRST));
    }

    public function testShouldGetLowBytesAsDoubleWord()
    {
        $quadWord = new QuadWord("\xFF\xFF\x7F\xFF\x00\x00\x01\x00");

        $this->assertEquals("\x00\x00\x01\x00", $quadWord->getLowBytesAsDoubleWord()->getData());
    }

    public function testShouldGetHighBytesAsDoubleWord()
    {
        $quadWord = new QuadWord("\xFF\xFF\x7F\xFF\x00\x00\x00\x00");

        $this->assertEquals("\xFF\xFF\x7F\xFF", $quadWord->getHighBytesAsDoubleWord()->getData());
    }
}