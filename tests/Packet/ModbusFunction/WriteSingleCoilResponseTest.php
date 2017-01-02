<?php
namespace Tests\Packet\ModbusFunction;


use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleCoilResponse;
use PHPUnit\Framework\TestCase;

class WriteSingleCoilResponseTest extends TestCase
{
    public function testPacketToString()
    {
        $this->assertEquals(
            "\x81\x80\x00\x00\x00\x05\x03\x05\x02\xFF\x00",
            (new WriteSingleCoilResponse("\xFF\x00", 3, 33152))->__toString()
        );
    }

    public function testOnPacketProperties()
    {
        $packet = new WriteSingleCoilResponse("\xFF\x00", 3, 33152);
        $this->assertEquals(IModbusPacket::WRITE_SINGLE_COIL, $packet->getFunctionCode());

        $this->assertEquals("\xFF\x0", $packet->getRawData());
        $this->assertEquals([0xFF, 0x0], $packet->getData());
        $this->assertEquals(true, $packet->isCoil());

        $header = $packet->getHeader();
        $this->assertEquals(33152, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(5, $header->getLength());
        $this->assertEquals(3, $header->getUnitId());
    }

    public function testOffPacketProperties()
    {
        $packet = new WriteSingleCoilResponse("\x00\x00", 3, 33152);
        $this->assertEquals(IModbusPacket::WRITE_SINGLE_COIL, $packet->getFunctionCode());

        $this->assertEquals("\x00\x0", $packet->getRawData());
        $this->assertEquals([0x0, 0x0], $packet->getData());
        $this->assertEquals(false, $packet->isCoil());

        $header = $packet->getHeader();
        $this->assertEquals(33152, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(5, $header->getLength());
        $this->assertEquals(3, $header->getUnitId());
    }

}