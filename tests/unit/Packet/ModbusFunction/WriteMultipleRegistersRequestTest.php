<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleRegistersRequest;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Endian;
use ModbusTcpClient\Utils\Types;
use PHPUnit\Framework\TestCase;

class WriteMultipleRegistersRequestTest extends TestCase
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
        // Field:                   Size in packet
        // transaction id: 0138     (2 bytes)
        // protocol id: 0000        (2 bytes)
        // length: 000d             (2 bytes) = hex:0d -> dec: 13
        //                                      (unit id size + function code size + start address size +
        //                                       register count size + registers byte size + registers bytes)
        // unit id: 11              (1 byte)
        // function code: 10        (1 byte)
        // start address: 0410      (2 bytes)
        // registersCount: 0003     (2 bytes)
        // registersBytesSize: 06   (1 byte)
        // registers: 00c8 0082 8701 (3 registers = 6 bytes)
        $this->assertEquals(
            "\x01\x38\x00\x00\x00\x0d\x11\x10\x04\x10\x00\x03\x06\x00\xC8\x00\x82\x87\x01",
            (new WriteMultipleRegistersRequest(0x0410, [
                Types::toByte(200),
                Types::toInt16(130, Endian::BIG_ENDIAN_LOW_WORD_FIRST),
                Types::toUint16(34561, Endian::BIG_ENDIAN_LOW_WORD_FIRST)
            ], 0x11, 0x0138))->__toString()
        );
    }

    public function testValidateEmptyRegistersThrowsException()
    {
        $this->expectExceptionMessage("registers count out of range (1-124): 0");
        $this->expectException(InvalidArgumentException::class);

        (new WriteMultipleRegistersRequest(107, [], 17, 1))->__toString();
    }

    public function testPacketProperties()
    {
        $registers = [Types::toByte(200), Types::toInt16(130), Types::toUint16(34561)];
        $packet = new WriteMultipleRegistersRequest(107, $registers, 17, 1);
        $this->assertEquals(ModbusPacket::WRITE_MULTIPLE_REGISTERS, $packet->getFunctionCode());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals($registers, $packet->getRegisters());

        $header = $packet->getHeader();
        $this->assertEquals(1, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(13, $header->getLength());
        $this->assertEquals(17, $header->getUnitId());
    }

    public function testParse()
    {
        $packet = WriteMultipleRegistersRequest::parse("\x01\x38\x00\x00\x00\x0d\x11\x10\x04\x10\x00\x03\x06\x00\xC8\x00\x82\x87\x01");
        $this->assertEquals($packet, (new WriteMultipleRegistersRequest(0x0410, [
            Types::toByte(200),
            Types::toInt16(130, Endian::BIG_ENDIAN_LOW_WORD_FIRST),
            Types::toUint16(34561, Endian::BIG_ENDIAN_LOW_WORD_FIRST)
        ], 0x11, 0x0138))->__toString());
        $this->assertEquals(0x0410, $packet->getStartAddress());
        $this->assertEquals(["\x00\xC8", "\x00\x82", "\x87\x01"], $packet->getRegisters());

        $header = $packet->getHeader();
        $this->assertEquals(0x0138, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(13, $header->getLength());
        $this->assertEquals(0x11, $header->getUnitId());
    }

    public function testParseShouldReturnErrorResponseForTooShortPacket()
    {
        $packet = WriteMultipleRegistersRequest::parse("\x01\x38\x00\x00\x00\x0d\x11\x10\x04\x10\x00\x03\x06\x00");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        // transaction id is random
        $toString[0] = "\x00";
        $toString[1] = "\x00";
        self::assertEquals("\x00\x00\x00\x00\x00\x03\x00\x90\x04", $toString);
    }

    public function testParseShouldReturnErrorResponseForTooShortPacketByByteCount()
    {
        $packet = WriteMultipleRegistersRequest::parse("\x01\x38\x00\x00\x00\x0d\x11\x10\x04\x10\x00\x03\x01\x00\xC8\x00\x82\x87\x01");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        // transaction id is random
        $toString[0] = "\x00";
        $toString[1] = "\x00";
        self::assertEquals("\x00\x00\x00\x00\x00\x03\x11\x90\x03", $toString);
    }

    public function testParseShouldReturnErrorResponseForInvalidFunction()
    {
        $packet = WriteMultipleRegistersRequest::parse("\x01\x38\x00\x00\x00\x0d\x11\x00\x04\x10\x00\x03\x06\x00\xC8\x00\x82\x87\x01");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        self::assertEquals("\x01\x38\x00\x00\x00\x03\x11\x90\x01", $toString);
    }
}
