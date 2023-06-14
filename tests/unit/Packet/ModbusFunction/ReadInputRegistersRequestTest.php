<?php

namespace Tests\Packet\ModbusFunction;


use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersRequest;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class ReadInputRegistersRequestTest extends TestCase
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

    public function testParse()
    {
        $packet = ReadInputRegistersRequest::parse("\x00\x01\x00\x00\x00\x06\x11\x04\x00\x6B\x00\x03");
        $this->assertEquals($packet, (new ReadInputRegistersRequest(107, 3, 17, 1))->__toString());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals(3, $packet->getQuantity());

        $header = $packet->getHeader();
        $this->assertEquals(1, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(17, $header->getUnitId());
    }

    public function testParseShouldReturnErrorResponseForTooShortPacket()
    {
        $packet = ReadInputRegistersRequest::parse("\x00\x01\x00\x00\x00\x06\x11\x04\x00\x6B\x00");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        // transaction id is random
        $toString[0] = "\x00";
        $toString[1] = "\x00";
        self::assertEquals("\x00\x00\x00\x00\x00\x03\x00\x84\x04", $toString);
    }

    public function testParseShouldReturnErrorResponseForInvalidFunction()
    {
        $packet = ReadInputRegistersRequest::parse("\x00\x01\x00\x00\x00\x06\x11\x00\x00\x6B\x00\x03");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        self::assertEquals("\x00\x01\x00\x00\x00\x03\x11\x84\x01", $toString);
    }

    public function testParseShouldReturnErrorResponseForInvalidQuantity()
    {
        $packet = ReadInputRegistersRequest::parse("\x00\x01\x00\x00\x00\x06\x11\x04\x00\x6B\x00\x00");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        self::assertEquals("\x00\x01\x00\x00\x00\x03\x11\x84\x03", $toString);
    }
}
