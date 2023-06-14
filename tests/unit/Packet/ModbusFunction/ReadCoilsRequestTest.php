<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsRequest;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class ReadCoilsRequestTest extends TestCase
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
            "\x00\x01\x00\x00\x00\x06\x10\x01\x00\x6B\x00\x03",
            (new ReadCoilsRequest(107, 3, 16, 1))->__toString()
        );
    }

    public function testPacketProperties()
    {
        $packet = new ReadCoilsRequest(107, 3, 17, 1);
        $this->assertEquals(ModbusPacket::READ_COILS, $packet->getFunctionCode());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals(3, $packet->getQuantity());

        $header = $packet->getHeader();
        $this->assertEquals(1, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(17, $header->getUnitId());
    }

    public function testShouldThrowExceptionOnNullQuantity()
    {
        $this->expectException(\TypeError::class);

        new ReadCoilsRequest(107, null, 17, 1);
    }

    public function testShouldThrowExceptionOnBelowLimitQuantity()
    {
        $this->expectExceptionMessage("quantity is not set or out of range (1-2048):");
        $this->expectException(InvalidArgumentException::class);

        new ReadCoilsRequest(107, 0, 17, 1);
    }

    public function testShouldThrowExceptionOnOverLimitQuantity()
    {
        $this->expectExceptionMessage("quantity is not set or out of range (1-2048):");
        $this->expectException(InvalidArgumentException::class);

        new ReadCoilsRequest(107, 256 * 8 + 1, 17, 1);
    }

    public function testParse()
    {
        $packet = ReadCoilsRequest::parse("\x00\x01\x00\x00\x00\x06\x10\x01\x00\x6B\x00\x03");
        $this->assertEquals($packet, (new ReadCoilsRequest(107, 3, 16, 1))->__toString());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals(3, $packet->getQuantity());

        $header = $packet->getHeader();
        $this->assertEquals(1, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(16, $header->getUnitId());
    }

    public function testParseShouldReturnErrorResponseForTooShortPacket()
    {
        $packet = ReadCoilsRequest::parse("\x00\x01\x00\x00\x00\x06\x10\x01\x00\x6B\x00");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        // transaction id is random
        $toString[0] = "\x00";
        $toString[1] = "\x00";
        self::assertEquals("\x00\x00\x00\x00\x00\x03\x00\x81\x04", $toString);
    }

    public function testParseShouldReturnErrorResponseForInvalidPDULength()
    {
        // length is 5 but should be 6
        $packet = ReadCoilsRequest::parse("\x00\x01\x00\x00\x00\x05\x10\x01\x00\x6B\x00\x03");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        // transaction id is random
        $toString[0] = "\x00";
        $toString[1] = "\x00";
        self::assertEquals("\x00\x00\x00\x00\x00\x03\x10\x81\x03", $toString);
    }

    public function testParseShouldReturnErrorResponseForInvalidFunction()
    {
        $packet = ReadCoilsRequest::parse("\x00\x01\x00\x00\x00\x06\x10\x02\x00\x6B\x00\x03");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        self::assertEquals("\x00\x01\x00\x00\x00\x03\x10\x81\x01", $toString);
    }

    public function testParseShouldReturnErrorResponseForInvalidQuantity()
    {
        $packet = ReadCoilsRequest::parse("\x00\x01\x00\x00\x00\x06\x10\x02\x00\x6B\x00\x00");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        self::assertEquals("\x00\x01\x00\x00\x00\x03\x10\x81\x01", $toString);
    }
}
