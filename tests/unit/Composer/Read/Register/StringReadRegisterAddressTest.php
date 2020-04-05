<?php

namespace Tests\unit\Composer\Read\Register;


use ModbusTcpClient\Composer\Read\Register\StringReadRegisterAddress;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use PHPUnit\Framework\TestCase;

class StringReadRegisterAddressTest extends TestCase
{
    public function testGetSize()
    {
        $address = new StringReadRegisterAddress(1, 5);
        $this->assertEquals(3, $address->getSize());
    }

    public function testGetName()
    {
        $address = new StringReadRegisterAddress(1, 5, 'username');
        $this->assertEquals('username', $address->getName());
    }

    public function testDefaultGetName()
    {
        $address = new StringReadRegisterAddress(1, 5);
        $this->assertEquals('string_1_5', $address->getName());
    }

    public function testExtract()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x01\x00\xF8\x53\x65\x72\x00\x6E", 3, 33152);
        $address = new StringReadRegisterAddress(1, 5, 'username');

        $value = $address->extract($responsePacket);

        $this->assertEquals('Søren', $value);
    }

    public function testExtractWithCallback()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x01\x00\xF8\x53\x65\x72\x00\x6E", 3, 33152);
        $address = new StringReadRegisterAddress(1, 5, 'username', function ($value) {
            return 'prefix_' . $value; // transform value after extraction
        });

        $value = $address->extract($responsePacket);

        $this->assertEquals('prefix_Søren', $value);
    }

}
