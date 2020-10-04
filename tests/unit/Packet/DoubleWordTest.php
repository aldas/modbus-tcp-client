<?php

namespace Tests\Packet;


use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Packet\DoubleWord;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class DoubleWordTest extends TestCase
{
    public function testShouldConstructFromShorterData()
    {
        $dWord = new DoubleWord("\x7F\xFF");

        $this->assertEquals("\x00\x00\x7F\xFF", $dWord->getData());
    }

    public function testShouldNotConstructFromLongerData()
    {
        $this->expectExceptionMessage("Word can only be constructed from 1 to 4 bytes. Currently 5 bytes was given!");
        $this->expectException(ModbusException::class);

        new DoubleWord("\x01\x02\x03\x04\x05");
    }

    public function testShouldGetUInt32()
    {
        $dWord = new DoubleWord("\xFF\xFF\x7F\xFF");

        $this->assertEquals(2147483647, $dWord->getUInt32());
    }

    public function testShouldGetUInt32Types()
    {
        $dWordUpperLimit = new DoubleWord("\xFF\xFF\xFF\xFF");
        $dWord = new DoubleWord("\x00\x00\x00\x01");

        if (PHP_INT_SIZE === 8) {
            $this->assertTrue(is_int($dWordUpperLimit->getUInt32(Endian::BIG_ENDIAN)));
            $this->assertTrue(is_int($dWord->getUInt32(Endian::BIG_ENDIAN)));
        } else {
            $this->assertTrue(is_float($dWordUpperLimit->getUInt32(Endian::BIG_ENDIAN)));
            $this->assertTrue(is_int($dWord->getUInt32(Endian::BIG_ENDIAN)));
        }

        $this->assertEquals(4294967295, $dWordUpperLimit->getUInt32(Endian::BIG_ENDIAN));
        $this->assertEquals(1, $dWord->getUInt32(Endian::BIG_ENDIAN));
    }

    public function testShouldGetInt32()
    {
        $dWord = new DoubleWord("\x00\x00\x80\x00");

        $this->assertEquals(-2147483648, $dWord->getInt32());
    }

    public function testShouldGetFloat()
    {
        $dWord = new DoubleWord("\xaa\xab\x3f\x2a");

        $this->assertEqualsWithDelta(0.6666666, $dWord->getFloat(), 0.0000001);
    }

    public function testShouldGetLowBytesAsWord()
    {
        $dWord = new DoubleWord("\xaa\xab\x3f\x2a");

        $this->assertEquals("\x3f\x2a", $dWord->getLowBytesAsWord()->getData());
    }

    public function testShouldGetHighBytesAsWord()
    {
        $dWord = new DoubleWord("\xaa\xab\x3f\x2a");

        $this->assertEquals("\xaa\xab", $dWord->getHighBytesAsWord()->getData());
    }

    public function testShouldCombineToQuadWord()
    {
        $lowDoubleWord = new DoubleWord("\xaa\xab\x3f\x2a");
        $highDoubleWord = new DoubleWord("\x01\x02\x03\x04");

        $quadWord = $highDoubleWord->combine($lowDoubleWord);

        $this->assertEquals("\x01\x02\x03\x04\xaa\xab\x3f\x2a", $quadWord->getData());
    }

}
