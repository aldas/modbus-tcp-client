<?php

namespace Tests\unit\Composer\Read\Register;


use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Composer\Read\Register\ByteReadRegisterAddress;
use PHPUnit\Framework\TestCase;

class ByteReadRegisterAddressTest extends TestCase
{

    public function testGetSize()
    {
        $address = new ByteReadRegisterAddress(1, true);
        $this->assertEquals(1, $address->getSize());
    }

    public function testGetName()
    {
        $address = new ByteReadRegisterAddress(1, true, 'direction');
        $this->assertEquals('direction', $address->getName());
    }

    public function testDefaultGetName()
    {
        $address = new ByteReadRegisterAddress(1, true);
        $this->assertEquals('byte_1_1', $address->getName());

        $address = new ByteReadRegisterAddress(1, false);
        $this->assertEquals('byte_1_0', $address->getName());
    }

    public function testExtract()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x02\x00\x05", 3, 33152);

        $this->assertEquals(5, (new ByteReadRegisterAddress(0, true))->extract($responsePacket));
        $this->assertEquals(0, (new ByteReadRegisterAddress(0, false))->extract($responsePacket));
    }

    public function testExtractWithCallback()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x02\x00\x05", 3, 33152);

        $address = new ByteReadRegisterAddress(0, true, null, function ($data) {
            return 'prefix_' . $data;
        });
        $this->assertEquals('prefix_5', $address->extract($responsePacket));
    }
}
