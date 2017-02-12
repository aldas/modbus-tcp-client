<?php

namespace Tests\Packet;

use ModbusTcpClient\Packet\Word;
use PHPUnit\Framework\TestCase;

class WordTest extends TestCase
{
    public function testShouldConstructEvenFrom1ByteOfData()
    {
        $word = new Word("\xFF");
        $this->assertEquals(255, $word->getUInt16BE());
    }

    /**
     * @expectedException \ModbusTcpClient\ModbusException
     * @expectedExceptionMessage Word can only be constructed from 1 or 2 bytes. Currently 3 bytes was given!
     */
    public function testShouldThrowExceptionForHugeData()
    {
        new Word("\xFF\xFF\xFF");
    }

    /**
     * @expectedException \ModbusTcpClient\ModbusException
     * @expectedExceptionMessage Word can only be constructed from 1 or 2 bytes. Currently 0 bytes was given!
     */
    public function testShouldThrowExceptionForNullData()
    {
        new Word(null);
    }

    /**
     * @expectedException \ModbusTcpClient\ModbusException
     * @expectedExceptionMessage Word can only be constructed from 1 or 2 bytes. Currently 0 bytes was given!
     */
    public function testShouldThrowExceptionForEmptyData()
    {
        new Word("");
    }

    public function testShouldGetUint16BE()
    {
        $word = new Word("\x80\x00");
        $this->assertEquals(32768, $word->getUInt16BE());
    }

    public function testShouldGetint16BE()
    {
        $word = new Word("\x80\x00");
        $this->assertEquals(-32768, $word->getInt16BE());
    }

    public function testShouldGetData()
    {
        $word = new Word("\x7F\xFF");
        $this->assertEquals("\x7F\xFF", $word->getData());
    }

    public function testShouldGetBytes()
    {
        $word = new Word("\x7F\xFF");
        $this->assertEquals([0x7F, 0xFF], $word->getBytes());
    }

    public function testShouldGetLowByteAsInt()
    {
        $word = new Word("\x7F\xFF");
        $this->assertEquals(255, $word->getLowByteAsInt());
    }

    public function testShouldGetHighByteAsInt()
    {
        $word = new Word("\x7F\xFF");
        $this->assertEquals(0x7F, $word->getHighByteAsInt());
    }

}