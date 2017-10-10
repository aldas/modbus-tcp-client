<?php

namespace Tests\Packet\ModbusFunction;


use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersRequest;
use PHPUnit\Framework\TestCase;

class ReadInputRegistersRequestTest extends TestCase
{
    public function testPacketToString()
    {
        $this->assertEquals(
            "\x00\x01\x00\x00\x00\x06\x11\x04\x00\x6B\x00\x03",
            (new ReadInputRegistersRequest(107, 3, 17, 1))->__toString()
        );
    }

    public function testPacketProperties()
    {
        $packet = new ReadInputRegistersRequest(107, 3, 17, 1);
        $this->assertEquals(ModbusPacket::READ_INPUT_REGISTERS, $packet->getFunctionCode());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals(3, $packet->getQuantity());

        $header = $packet->getHeader();
        $this->assertEquals(1, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(17, $header->getUnitId());
    }

    public function testPacketMandatoryProperties()
    {
        $packet = new ReadInputRegistersRequest(107, 3);

        $this->assertEquals(ModbusPacket::READ_INPUT_REGISTERS, $packet->getFunctionCode());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals(3, $packet->getQuantity());

        $header = $packet->getHeader();
        $this->assertNotNull($header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(0, $header->getUnitId());
    }

}