<?php

namespace Tests\Packet\ModbusFunction;


use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleRegisterResponse;
use PHPUnit\Framework\TestCase;

class WriteSingleRegisterResponseTest extends TestCase
{
    public function testPacketToString()
    {
        $this->assertEquals(
            "\x81\x80\x00\x00\x00\x06\x03\x06\x00\x02\xFF\x00",
            (new WriteSingleRegisterResponse("\x00\x02\xFF\x00", 3, 33152))->__toString()
        );
    }

    public function testOnPacketProperties()
    {
        $packet = new WriteSingleRegisterResponse("\x00\x02\xFF\x00", 3, 33152);
        $this->assertEquals(IModbusPacket::WRITE_SINGLE_REGISTER, $packet->getFunctionCode());

        $this->assertEquals(0xFF00, $packet->getValue());

        $header = $packet->getHeader();
        $this->assertEquals(33152, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(3, $header->getUnitId());
    }

}