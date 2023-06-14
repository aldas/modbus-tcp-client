<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleCoilRequest;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class WriteSingleCoilRequestTest extends TestCase
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

    public function testParse()
    {
        $packet = WriteSingleCoilRequest::parse("\x00\x01\x00\x00\x00\x06\x11\x05\x00\x6B\xFF\x00");
        $this->assertEquals($packet, (new WriteSingleCoilRequest(107, true, 17, 1))->__toString());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals(true, $packet->isCoil());

        $header = $packet->getHeader();
        $this->assertEquals(1, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(17, $header->getUnitId());
    }

    public function testParseShouldReturnErrorResponseForTooShortPacket()
    {
        $packet = WriteSingleCoilRequest::parse("\x00\x01\x00\x00\x00\x06\x11\x05\x00\x6B\xFF");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        // transaction id is random
        $toString[0] = "\x00";
        $toString[1] = "\x00";
        self::assertEquals("\x00\x00\x00\x00\x00\x03\x00\x85\x04", $toString);
    }

    public function testParseShouldReturnErrorResponseForInvalidFunction()
    {
        $packet = WriteSingleCoilRequest::parse("\x00\x01\x00\x00\x00\x06\x11\x00\x00\x6B\xFF\x00");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        self::assertEquals("\x00\x01\x00\x00\x00\x03\x11\x85\x01", $toString);
    }
}
