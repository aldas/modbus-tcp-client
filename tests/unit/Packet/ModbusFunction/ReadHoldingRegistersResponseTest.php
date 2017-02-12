<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use PHPUnit\Framework\TestCase;

class ReadHoldingRegistersResponseTest extends TestCase
{
    public function testPacketToString()
    {
        $this->assertEquals(
            "\x81\x80\x00\x00\x00\x05\x03\x03\x02\xCD\x6B",
            (new ReadHoldingRegistersResponse("\x02\xCD\x6B", 3, 33152))->__toString()
        );
    }

    public function testPacketProperties()
    {
        $packet = new ReadHoldingRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01", 3, 33152);
        $this->assertEquals(IModbusPacket::READ_HOLDING_REGISTERS, $packet->getFunctionCode());

        $this->assertEquals([0xCD, 0x6B, 0x0, 0x0, 0x0, 0x1], $packet->getData());

        $this->assertCount(3, $packet->getWords());
        $this->assertEquals([0x0, 0x1], $packet->getWords()[2]->getBytes());

        $header = $packet->getHeader();
        $this->assertEquals(33152, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(9, $header->getLength());
        $this->assertEquals(3, $header->getUnitId());
    }

    public function testGetWords()
    {
        $packet = new ReadHoldingRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01", 3, 33152);

        $words = $packet->getWords();
        $this->assertCount(3, $words);

        $this->assertEquals("\xCD\x6B", $words[0]->getData());
        $this->assertEquals([0xCD, 0x6B], $words[0]->getBytes());

        $this->assertEquals([0x0, 0x0], $words[1]->getBytes());
        $this->assertEquals([0x0, 0x1], $words[2]->getBytes());
    }

}