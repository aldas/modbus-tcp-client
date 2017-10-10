<?php
namespace Tests\Packet\ModbusFunction;


use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleCoilResponse;
use PHPUnit\Framework\TestCase;

class WriteSingleCoilResponseTest extends TestCase
{
    public function testPacketToString()
    {
        $this->assertEquals(
            "\x81\x80\x00\x00\x00\x06\x03\x05\x00\x02\xFF\x00",
            (new WriteSingleCoilResponse("\x00\x02\xFF\x00", 3, 33152))->__toString()
        );
    }

    public function testOnPacketProperties()
    {
        $packet = new WriteSingleCoilResponse("\x00\x02\xFF\x00", 3, 33152);
        $this->assertEquals(ModbusPacket::WRITE_SINGLE_COIL, $packet->getFunctionCode());

        $this->assertEquals(true, $packet->isCoil());

        $header = $packet->getHeader();
        $this->assertEquals(33152, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(3, $header->getUnitId());
    }

    public function testOffPacketProperties()
    {
        $packet = new WriteSingleCoilResponse("\x00\x02\x00\x00", 3, 33152);
        $this->assertEquals(ModbusPacket::WRITE_SINGLE_COIL, $packet->getFunctionCode());

        $this->assertEquals(false, $packet->isCoil());

        $header = $packet->getHeader();
        $this->assertEquals(33152, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(3, $header->getUnitId());
    }

}