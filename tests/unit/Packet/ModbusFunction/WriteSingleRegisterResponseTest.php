<?php

namespace Tests\Packet\ModbusFunction;


use ModbusTcpClient\Packet\ModbusFunction\WriteSingleRegisterResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class WriteSingleRegisterResponseTest extends TestCase
{
    protected function setUp(): void
    {
        Endian::$defaultEndian = Endian::LITTLE_ENDIAN; // packets are big endian. setting to default to little should not change output
    }

    protected function tearDown(): void
    {
        Endian::$defaultEndian = Endian::BIG_ENDIAN_LOW_WORD_FIRST;
    }

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
        $this->assertEquals(ModbusPacket::WRITE_SINGLE_REGISTER, $packet->getFunctionCode());

        $this->assertEquals(0xFF00, $packet->getWord()->getUInt16(Endian::BIG_ENDIAN_LOW_WORD_FIRST));

        $header = $packet->getHeader();
        $this->assertEquals(33152, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(3, $header->getUnitId());
    }

    public function testWithStartAddress()
    {
        $packet = new WriteSingleRegisterResponse("\x00\x02\xFF\x00", 3, 33152);
        $packetWithStartAddress = $packet->withStartAddress(1);

        $this->assertEquals(2, $packet->getStartAddress());
        $this->assertEquals(2, $packetWithStartAddress->getStartAddress());
    }

}