<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleCoilsRequest;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class WriteMultipleCoilsRequestTest extends TestCase
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
            "\x01\x38\x00\x00\x00\x08\x11\x0F\x04\x10\x00\x03\x01\x05",
            (new WriteMultipleCoilsRequest(0x0410, [true, false, true], 0x11, 0x0138))->__toString()
        );
    }

    public function testPacketToStringWithMultiByte()
    {
        $this->assertEquals(
            "\x01\x01\x00\x00\x00\x0a\x11\x0F\x00\x6B\x00\x14\x03\x55\x0\x09",
            (new WriteMultipleCoilsRequest(107, [
                1, 0, 1, 0, 1, 0, 1, 0, // dec: 85, hex: x55
                0, 0, 0, 0, 0, 0, 0, 0, // dec: 0, hex x0
                1, 0, 0, 1 // dec: 9, hex: x9
            ], 17, 257))->__toString()
        );
    }

    public function testValidateEmptyCoils()
    {
        $this->expectExceptionMessage("coils count out of range (1-2048): 0");
        $this->expectException(InvalidArgumentException::class);

        (new WriteMultipleCoilsRequest(107, [], 17, 1))->__toString();
    }

    public function testOnPacketProperties()
    {
        $coils = [true, false, true];
        $packet = new WriteMultipleCoilsRequest(107, $coils, 17, 1);
        $this->assertEquals(ModbusPacket::WRITE_MULTIPLE_COILS, $packet->getFunctionCode());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals($coils, $packet->getCoils());

        $header = $packet->getHeader();
        $this->assertEquals(1, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(8, $header->getLength());
        $this->assertEquals(17, $header->getUnitId());
    }

    public function testParse()
    {
        $packet = WriteMultipleCoilsRequest::parse("\x01\x38\x00\x00\x00\x08\x11\x0F\x04\x10\x00\x03\x01\x05");
        $this->assertEquals($packet, (new WriteMultipleCoilsRequest(0x0410, [true, false, true], 0x11, 0x0138))->__toString());
        $this->assertEquals(0x0410, $packet->getStartAddress());
        $this->assertEquals([true, false, true], $packet->getCoils());

        $header = $packet->getHeader();
        $this->assertEquals(0x0138, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(8, $header->getLength());
        $this->assertEquals(0x11, $header->getUnitId());
    }

    public function testParseShouldReturnErrorResponseForTooShortPacket()
    {
        $packet = WriteMultipleCoilsRequest::parse("\x01\x38\x00\x00\x00\x08\x11\x0F\x04\x10\x00\x03\x01");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        // transaction id is random
        $toString[0] = "\x00";
        $toString[1] = "\x00";
        self::assertEquals("\x00\x00\x00\x00\x00\x03\x00\x8f\x04", $toString);
    }

    public function testParseShouldReturnErrorResponseForTooShortPacketByQuantity()
    {
        $packet = WriteMultipleCoilsRequest::parse("\x01\x38\x00\x00\x00\x08\x11\x0F\x04\x10\x00\xff\x01\x05");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        // transaction id is random
        $toString[0] = "\x00";
        $toString[1] = "\x00";
        self::assertEquals("\x00\x00\x00\x00\x00\x03\x11\x8f\x03", $toString);
    }

    public function testParseShouldReturnErrorResponseForInvalidFunction()
    {
        $packet = WriteMultipleCoilsRequest::parse("\x01\x38\x00\x00\x00\x08\x11\x00\x04\x10\x00\x03\x01\x05");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        self::assertEquals("\x01\x38\x00\x00\x00\x03\x11\x8f\x01", $toString);
    }
}
