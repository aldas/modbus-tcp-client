<?php

namespace Tests\unit\Composer\Write;


use ModbusTcpClient\Composer\Write\StringWriteAddress;
use PHPUnit\Framework\TestCase;

class StringWriteAddressTest extends TestCase
{
    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage Out of range string length for given! length: '0', address: 0
     */
    public function testConstructorByteRangeUnderflow()
    {
        new StringWriteAddress(0, 'hello', 0);
    }

    public function testGetSize()
    {
        $this->assertEquals(5, (new StringWriteAddress(0, 'hello', 10))->getSize());
        $this->assertEquals(3, (new StringWriteAddress(0, 'hello', 5))->getSize());
        $this->assertEquals(1, (new StringWriteAddress(0, '', 1))->getSize());
        $this->assertEquals(5, (new StringWriteAddress(0, '', 10))->getSize());
    }


    public function testToBinary()
    {
        $address = new StringWriteAddress(0, 'Søren', 10, 'cp1252');

        $this->assertEquals("\x00\x6E\x65\x72\xF8\x53\x00\x00\x00\x00", $address->toBinary());
    }

}