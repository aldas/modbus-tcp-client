<?php

namespace Tests\unit\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusFunction\GetCommEventCounterResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class GetCommEventCounterResponseTest extends TestCase
{
    protected function setUp(): void
    {
        Endian::$defaultEndian = Endian::LITTLE_ENDIAN; // packets are big endian. setting to default to little should not change output
    }

    protected function tearDown(): void
    {
        Endian::$defaultEndian = Endian::BIG_ENDIAN_LOW_WORD_FIRST;
    }

    public function testOnPacketToString()
    {
        // Field:                    Size in packet
        $payload = "\x01\x38" . // transaction id: 0138 (2 bytes)
            "\x00\x00" . // protocol id: 0000           (2 bytes)
            "\x00\x06" .  // length: 0006               (2 bytes) (6 bytes after this field)
            "\x11" . // unit id: 11                     (1 byte)
            "\x0b" . // function code: 0b               (1 byte)
            "\xFF\xFF" . // status: FFFF                (2 bytes)
            "\x01\x02" . // AND mask: 258 (0x0102)      (2 bytes)
            '';
        $this->assertEquals(
            $payload,
            (new GetCommEventCounterResponse(
                "\xFF\xFF\x01\x02",
                0x11,
                0x0138,
            ))->__toString()
        );
    }

    public function testOnPacketProperties()
    {
        $packet = new GetCommEventCounterResponse(
            "\xFF\xFF\x01\x02",
            0x11,
            0x0138,
        );
        $this->assertEquals(ModbusPacket::GET_COMM_EVENT_COUNTER, $packet->getFunctionCode());
        $this->assertEquals(0xFFFF, $packet->getStatus());
        $this->assertEquals(258, $packet->getEventCount());

        $header = $packet->getHeader();
        $this->assertEquals(0x0138, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(0x11, $header->getUnitId());
    }

    public function testWithStartAddress()
    {
        $packet = new GetCommEventCounterResponse(
            "\xFF\xFF\x01\x02",
            0x11,
            0x0138,
        );
        $packetWithStartAddress = $packet->withStartAddress(1);

        $this->assertEquals(ModbusPacket::GET_COMM_EVENT_COUNTER, $packetWithStartAddress->getFunctionCode());
        $this->assertEquals(0xFFFF, $packetWithStartAddress->getStatus());
        $this->assertEquals(258, $packetWithStartAddress->getEventCount());

        $header = $packetWithStartAddress->getHeader();
        $this->assertEquals(0x0138, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(0x11, $header->getUnitId());
    }
}
