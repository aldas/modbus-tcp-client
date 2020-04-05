<?php

namespace Tests\unit\Composer\Read\Register;


use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Composer\Read\Register\BitReadRegisterAddress;
use PHPUnit\Framework\TestCase;

class BitReadRegisterAddressTest extends TestCase
{
    public function testGetSize()
    {
        $address = new BitReadRegisterAddress(1, 0);
        $this->assertEquals(1, $address->getSize());
    }

    public function testGetName()
    {
        $address = new BitReadRegisterAddress(1, 0, 'alarm1_do');
        $this->assertEquals('alarm1_do', $address->getName());
    }

    public function testDefaultGetName()
    {
        $address = new BitReadRegisterAddress(1, 1);
        $this->assertEquals('bit_1_1', $address->getName());
    }

    public function testExtract()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x02\x00\x05", 3, 33152);

        $this->assertTrue((new BitReadRegisterAddress(0, 0))->extract($responsePacket));
        $this->assertFalse((new BitReadRegisterAddress(0, 1))->extract($responsePacket));
        $this->assertTrue((new BitReadRegisterAddress(0, 2))->extract($responsePacket));
    }

    public function testExtractWithCallback()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x02\x00\x05", 3, 33152);

        $address = new BitReadRegisterAddress(0, 0, 'name', function ($value) {
            return 'prefix_' . $value; // transform value after extraction
        });
        $this->assertEquals('prefix_1', $address->extract($responsePacket));
    }
}
