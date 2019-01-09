<?php

namespace Tests\Packet;

use ModbusTcpClient\Packet\QuadWord;
use ModbusTcpClient\Packet\Word;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;
use PHPUnit_Framework_Error;

class QuadWordTest extends TestCase
{
    public function testShouldConstructFromShorterData()
    {
        $dWord = new QuadWord("\x7F\xFF");

        $this->assertEquals("\x00\x00\x00\x00\x00\x00\x7F\xFF", $dWord->getData());
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ModbusException
     * @expectedExceptionMessage QuadWord can only be constructed from 1 to 8 bytes. Currently 9 bytes was given!
     */
    public function testShouldNotConstructFromLongerData()
    {
        new QuadWord("\x01\x02\x03\x04\x05\x06\x07\x08\x09");
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\OverflowException
     * @expectedExceptionMessage 64-bit PHP supports only up to 63-bit signed integers. Current input has 64th bit set and overflows. Hex: ffffffffffffffff
     */
    public function testShouldOverflow()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('64-bit format codes are not available for 32-bit versions of PHP');
        }

        // 64-bit PHP supports only up to 63-bit signed integers. Parsing this value results '-1' which is overflow
        $quadWord = new QuadWord("\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF");

        $this->assertEquals(2147483647, $quadWord->getUInt64(Endian::BIG_ENDIAN_LOW_WORD_FIRST));
    }

    public function testShouldGetUInt64()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('64-bit format codes are not available for 32-bit versions of PHP');
        }

        $quadWord = new QuadWord("\xFF\xFF\x7F\xFF\x00\x00\x00\x00");

        $this->assertEquals(2147483647, $quadWord->getUInt64(Endian::BIG_ENDIAN_LOW_WORD_FIRST));
    }

    public function testShouldGetInt64()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('64-bit format codes are not available for 32-bit versions of PHP');
        }

        $quadWord = new QuadWord("\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF");

        $this->assertEquals(-1, $quadWord->getInt64(Endian::BIG_ENDIAN_LOW_WORD_FIRST));
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

    public function testShouldCreateFromWords()
    {
        $quadWord = QuadWord::fromWords(
            new Word("\x01\x02"),
            new Word("\x03\x04"),
            new Word("\x05\x06"),
            new Word("\x07\x08")
        );

        $this->assertEquals("\x01\x02\x03\x04\x05\x06\x07\x08", $quadWord->getData());
    }

    /**
     * @requires PHP 7
     * @expectedException \TypeError
     */
    public function testShouldNotCreateFromWordsWhenParamNotWord()
    {
        QuadWord::fromWords(
            new Word("\x01\x02"),
            new Word("\x03\x04"),
            new Word("\x05\x06"),
            null
        );
    }
}