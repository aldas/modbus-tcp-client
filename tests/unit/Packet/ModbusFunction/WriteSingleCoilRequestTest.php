<?php
namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleCoilRequest;
use PHPUnit\Framework\TestCase;

class WriteSingleCoilRequestTest extends TestCase
{
    public function testOnPacketToString()
    {
        $this->assertEquals(
            "\x00\x01\x00\x00\x00\x06\x11\x05\x00\x6B\xFF\x00",
            (new WriteSingleCoilRequest(107, true, 17, 1))->__toString()
        );
    }

    public function testOffPacketToString()
    {
        $this->assertEquals(
            "\x00\x01\x00\x00\x00\x06\x11\x05\x00\x6B\x00\x00",
            (new WriteSingleCoilRequest(107, false, 17, 1))->__toString()
        );
    }

    public function testValidateCoilConvertedToBool()
    {
        $this->assertEquals(
            "\x00\x01\x00\x00\x00\x06\x11\x05\x00\x6B\x00\x00",
            (new WriteSingleCoilRequest(107, 0, 17, 1))->__toString()
        );
    }

    public function testOnPacketProperties()
    {
        $packet = new WriteSingleCoilRequest(107, true, 17, 1);
        $this->assertEquals(ModbusPacket::WRITE_SINGLE_COIL, $packet->getFunctionCode());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals(true, $packet->isCoil());

        $header = $packet->getHeader();
        $this->assertEquals(1, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(17, $header->getUnitId());
    }

    public function testOffPacketProperties()
    {
        $packet = new WriteSingleCoilRequest(107, false, 17, 1);
        $this->assertEquals(5, $packet->getFunctionCode());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals(false, $packet->isCoil());

        $header = $packet->getHeader();
        $this->assertEquals(1, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(17, $header->getUnitId());
    }
}