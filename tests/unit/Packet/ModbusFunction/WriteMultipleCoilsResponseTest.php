<?php

namespace Tests\Packet\ModbusFunction;


use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleCoilsResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class WriteMultipleCoilsResponseTest extends TestCase
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
        $this->assertEquals(
            "\x01\x38\x00\x00\x00\x06\x11\x0F\x04\x10\x00\x03",
            (new WriteMultipleCoilsResponse("\x04\x10\x00\x03", 0x11, 0x0138))->__toString()
        );
    }

    public function testPacketProperties()
    {
        $packet = new WriteMultipleCoilsResponse("\x04\x10\x00\x03", 3, 33152);
        $this->assertEquals(ModbusPacket::WRITE_MULTIPLE_COILS, $packet->getFunctionCode());

        $this->assertEquals(0x0410, $packet->getStartAddress());
        $this->assertEquals(0x03, $packet->getCoilCount());

        $header = $packet->getHeader();
        $this->assertEquals(33152, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(3, $header->getUnitId());
    }

}