<?php

namespace Tests\Packet;

use ModbusTcpClient\Packet\Word;
use PHPUnit\Framework\TestCase;

class WordTest extends TestCase
{
    public function testShouldConstructEvenFrom1ByteOfData()
    {
        $word = new Word("\xFF");
        $this->assertEquals(255, $word->getUInt16());
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ModbusException
     * @expectedExceptionMessage Word can only be constructed from 1 to 2 bytes. Currently 3 bytes was given!
     */
    public function testShouldThrowExceptionForHugeData()
    {
        new Word("\xFF\xFF\xFF");
    }

    /**
     * @expectedException \TypeError
     */
    public function testShouldThrowExceptionForNullData()
    {
        new Word(null);
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ModbusException
     * @expectedExceptionMessage Word can only be constructed from 1 to 2 bytes. Currently 0 bytes was given!
     */
    public function testShouldThrowExceptionForEmptyData()
    {
        new Word('');
    }

    public function testShouldGetUint16BE()
    {
        $word = new Word("\x80\x00");
        $this->assertEquals(32768, $word->getUInt16());
    }

    public function testShouldGetint16BE()
    {
        $word = new Word("\x80\x00");
        $this->assertEquals(-32768, $word->getInt16());
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

    public function testShouldSeeIfBitIsSet()
    {
        $word = new Word("\x02\x05");
        $this->assertTrue($word->isBitSet(0)); // 1 of low byte
        $this->assertTrue($word->isBitSet(2)); // 4 of low byte
        $this->assertTrue($word->isBitSet(9)); // 2 of high byte

        $this->assertFalse($word->isBitSet(1));
        $this->assertFalse($word->isBitSet(15));
    }

    public function testShouldCombineToDoubleWord()
    {
        $lowWord = new Word("\x01\x00");
        $highWord = new Word("\x03\x02");

        $doubleWord = $highWord->combine($lowWord);

        $this->assertEquals("\x03\x02\x01\x00", $doubleWord->getData());
    }

}