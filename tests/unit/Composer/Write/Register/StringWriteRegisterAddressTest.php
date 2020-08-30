<?php

namespace Tests\unit\Composer\Write\Register;


use ModbusTcpClient\Composer\Write\Register\StringWriteRegisterAddress;
use ModbusTcpClient\Exception\InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class StringWriteRegisterAddressTest extends TestCase
{
    public function testConstructorByteRangeUnderflow()
    {
        $this->expectExceptionMessage("Out of range string length for given! length: '0', address: 0");
        $this->expectException(InvalidArgumentException::class);

        new StringWriteRegisterAddress(0, 'hello', 0);
    }

    public function testGetSize()
    {
        $this->assertEquals(5, (new StringWriteRegisterAddress(0, 'hello', 10))->getSize());
        $this->assertEquals(3, (new StringWriteRegisterAddress(0, 'hello', 5))->getSize());
        $this->assertEquals(1, (new StringWriteRegisterAddress(0, '', 1))->getSize());
        $this->assertEquals(5, (new StringWriteRegisterAddress(0, '', 10))->getSize());
    }


    public function testToBinary()
    {
        $address = new StringWriteRegisterAddress(0, 'Søren', 10, 'cp1252');

        $this->assertEquals("\x00\x6E\x65\x72\xF8\x53\x00\x00\x00\x00", $address->toBinary());
    }

}
