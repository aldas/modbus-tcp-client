<?php

namespace Tests\unit\Composer\Read;


use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Composer\Read\ByteReadAddress;
use PHPUnit\Framework\TestCase;

class ByteAddressTest extends TestCase
{

    public function testGetSize()
    {
        $address = new ByteReadAddress(1, true);
        $this->assertEquals(1, $address->getSize());
    }

    public function testGetName()
    {
        $address = new ByteReadAddress(1, true, 'direction');
        $this->assertEquals('direction', $address->getName());
    }

    public function testDefaultGetName()
    {
        $address = new ByteReadAddress(1, true);
        $this->assertEquals('byte_1_1', $address->getName());

        $address = new ByteReadAddress(1, false);
        $this->assertEquals('byte_1_0', $address->getName());
    }

    public function testExtract()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x02\x00\x05", 3, 33152);

        $this->assertEquals(5, (new ByteReadAddress(0, true))->extract($responsePacket));
        $this->assertEquals(0, (new ByteReadAddress(0, false))->extract($responsePacket));
    }

    public function testExtractWithCallback()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x02\x00\x05", 3, 33152);

        $address = new ByteReadAddress(0, true, null, function ($data) {
            return 'prefix_' . $data;
        });
        $this->assertEquals('prefix_5', $address->extract($responsePacket));
    }
}