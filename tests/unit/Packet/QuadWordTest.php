<?php

namespace Tests\Packet;

use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Exception\OverflowException;
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

    public function testShouldNotConstructFromLongerData()
    {
        $this->expectExceptionMessage("QuadWord can only be constructed from 1 to 8 bytes. Currently 9 bytes was given!");
        $this->expectException(ModbusException::class);

        new QuadWord("\x01\x02\x03\x04\x05\x06\x07\x08\x09");
    }

    public function testShouldOverflow()
    {
        $this->expectExceptionMessage("64-bit PHP supports only up to 63-bit signed integers. Current input has 64th bit set and overflows. Hex: ffffffffffffffff");
        $this->expectException(OverflowException::class);

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

    public function testShouldGetDouble()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('64-bit format codes are not available for 32-bit versions of PHP');
        }

        $quadWord = new QuadWord("\x4d\x82\x30\x10\xcc\xc3\x41\xc1");

        $this->assertEqualsWithDelta(597263968.12737, $quadWord->getDouble(Endian::BIG_ENDIAN_LOW_WORD_FIRST), 0.00001);
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
     */
    public function testShouldNotCreateFromWordsWhenParamNotWord()
    {
        $this->expectException(\TypeError::class);

        QuadWord::fromWords(
            new Word("\x01\x02"),
            new Word("\x03\x04"),
            new Word("\x05\x06"),
            null
        );
    }
}
