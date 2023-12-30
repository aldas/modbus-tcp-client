<?php

namespace Tests\unit\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\GetCommEventCounterRequest;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class GetCommEventCounterRequestTest extends TestCase
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
            "\x00\x02" .  // length: 0002               (2 bytes) (2 bytes after this field)
            "\x11" . // unit id: 11                     (1 byte)
            "\x0b" . // function code: 0b               (1 byte)
            '';
        $this->assertEquals(
            $payload,
            (new GetCommEventCounterRequest(
                0x11,
                0x0138,
            ))->__toString()
        );
    }

    public function testOnPacketProperties()
    {
        $packet = new GetCommEventCounterRequest(
            0x11,
            0x0138
        );
        $this->assertEquals(ModbusPacket::GET_COMM_EVENT_COUNTER, $packet->getFunctionCode());

        $header = $packet->getHeader();
        $this->assertEquals(0x0138, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(2, $header->getLength());
        $this->assertEquals(0x11, $header->getUnitId());
    }

    public function testParse()
    {
        // Field:                    Size in packet
        $payload = "\x01\x38" . // transaction id: 0138 (2 bytes)
            "\x00\x00" . // protocol id: 0000           (2 bytes)
            "\x00\x02" .  // length: 0002               (2 bytes) (2 bytes after this field)
            "\x11" . // unit id: 11                     (1 byte)
            "\x0b" . // function code: 0b               (1 byte)
            '';
        $packet = GetCommEventCounterRequest::parse($payload);
        $this->assertEquals($packet, (new GetCommEventCounterRequest(
            0x11,
            0x0138
        ))->__toString());
        $this->assertEquals(ModbusPacket::GET_COMM_EVENT_COUNTER, $packet->getFunctionCode());

        $header = $packet->getHeader();
        $this->assertEquals(0x0138, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(2, $header->getLength());
        $this->assertEquals(0x11, $header->getUnitId());
    }

    public function testParseShouldReturnErrorResponseForTooShortPacket()
    {
        // Field:                    Size in packet
        $payload = "\x01\x38" . // transaction id: 0138 (2 bytes)
            "\x00\x00" . // protocol id: 0000           (2 bytes)
            "\x00\x01" .  // length: 0001               (2 bytes) (2 bytes after this field)
            "\x11" . // unit id: 11                     (1 byte)
            "\x0b" . // function code: 0b               (1 byte)
            '';
        $packet = GetCommEventCounterRequest::parse($payload);
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        // transaction id is random
        $toString[0] = "\x00";
        $toString[1] = "\x00";
        self::assertEquals("\x00\x00\x00\x00\x00\x03\x11\x8b\x03", $toString);
    }

    public function testParseShouldReturnErrorResponseForInvalidFunction()
    {
        // Field:                    Size in packet
        $payload = "\x01\x38" . // transaction id: 0138 (2 bytes)
            "\x00\x00" . // protocol id: 0000           (2 bytes)
            "\x00\x02" .  // length: 0002               (2 bytes) (2 bytes after this field)
            "\x11" . // unit id: 11                     (1 byte)
            "\x01" . // function code: 01               (1 byte) <-- should be 0x0b
            '';
        $packet = GetCommEventCounterRequest::parse($payload);
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        self::assertEquals("\x01\x38\x00\x00\x00\x03\x11\x8b\x01", $toString);
    }
}
