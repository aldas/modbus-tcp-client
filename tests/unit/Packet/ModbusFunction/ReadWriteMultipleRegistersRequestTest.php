<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadWriteMultipleRegistersRequest;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Endian;
use ModbusTcpClient\Utils\Types;
use PHPUnit\Framework\TestCase;

class ReadWriteMultipleRegistersRequestTest extends TestCase
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
            "\x00\x0f" .  // length: 000f               (2 bytes) (15 bytes after this field)
            "\x11" . // unit id: 11                     (1 byte)
            "\x17" . // function code: 17               (1 byte)
            "\x04\x10" . // read start address: 0410    (2 bytes)
            "\x00\x01" . // read quantity: 1            (2 bytes)
            "\x01\x12" . // write start address: 0112   (2 bytes)
            "\x00\x02" . // write quantity: 2           (2 bytes)
            "\x04" . // registersBytesSize: 4           (1 byte)
            "\x00\xc8\x00\x82" . // data: 00c8 0082     (2 registers = 4 bytes)
            '';
        $this->assertEquals(
            $payload,
            (new ReadWriteMultipleRegistersRequest(
                0x0410,
                1,
                0x0112,
                [Types::toByte(200), Types::toInt16(130, Endian::BIG_ENDIAN_LOW_WORD_FIRST)],
                0x11,
                0x0138
            ))->__toString()
        );
    }

    public function testValidateEmptyWriteRegisters()
    {
        $this->expectExceptionMessage("write registers count out of range (1-121): 0");
        $this->expectException(InvalidArgumentException::class);

        (new ReadWriteMultipleRegistersRequest(
            0x0410,
            1,
            0x0112,
            []
        ))->__toString();
    }

    public function testValidateReadQuantityUnderflow()
    {
        $this->expectExceptionMessage("read registers quantity out of range (1-125): 0");
        $this->expectException(InvalidArgumentException::class);

        (new ReadWriteMultipleRegistersRequest(
            0x0410,
            0,
            0x0112,
            [Types::toByte(200)]
        ))->__toString();
    }

    public function testValidateReadQuantityOverflow()
    {
        $this->expectExceptionMessage("read registers quantity out of range (1-125): 126");
        $this->expectException(InvalidArgumentException::class);

        (new ReadWriteMultipleRegistersRequest(
            0x0410,
            126,
            0x0112,
            [Types::toByte(200)]
        ))->__toString();
    }

    public function testValidateReadStartAddressUnderflow()
    {
        $this->expectExceptionMessage("startAddress is out of range: -1");
        $this->expectException(InvalidArgumentException::class);

        (new ReadWriteMultipleRegistersRequest(
            -1,
            1,
            0x0112,
            [Types::toByte(200)]
        ))->__toString();
    }

    public function testValidateReadStartAddressOverflow()
    {
        $this->expectExceptionMessage("startAddress is out of range: 65536");
        $this->expectException(InvalidArgumentException::class);

        (new ReadWriteMultipleRegistersRequest(
            Types::MAX_VALUE_UINT16 + 1,
            1,
            0x0112,
            [Types::toByte(200)]
        ))->__toString();
    }

    public function testValidateWriteStartAddressUnderflow()
    {
        $this->expectExceptionMessage("write registers start address out of range (0-65535): -1");
        $this->expectException(InvalidArgumentException::class);

        (new ReadWriteMultipleRegistersRequest(
            1,
            1,
            -1,
            [Types::toByte(200)]
        ))->__toString();
    }

    public function testValidateWriteStartAddressOverflow()
    {
        $this->expectExceptionMessage("write registers start address out of range (0-65535): 65536");
        $this->expectException(InvalidArgumentException::class);

        (new ReadWriteMultipleRegistersRequest(
            1,
            1,
            Types::MAX_VALUE_UINT16 + 1,
            [Types::toByte(200)]
        ))->__toString();
    }

    public function testOnPacketProperties()
    {
        $writeData = [Types::toByte(200), Types::toInt16(130)];
        $packet = new ReadWriteMultipleRegistersRequest(
            0x0410,
            1,
            0x0112,
            $writeData,
            0x11,
            0x0138
        );
        $this->assertEquals(ModbusPacket::READ_WRITE_MULTIPLE_REGISTERS, $packet->getFunctionCode());
        $this->assertEquals(0x0410, $packet->getStartAddress());
        $this->assertEquals(1, $packet->getReadQuantity());
        $this->assertEquals(0x0112, $packet->getWriteStartAddress());
        $this->assertEquals(2, $packet->getWriteRegisterCount());

        $this->assertEquals($writeData, $packet->getRegisters());

        $header = $packet->getHeader();
        $this->assertEquals(0x0138, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(15, $header->getLength());
        $this->assertEquals(0x11, $header->getUnitId());
    }

    public function testParse()
    {
        // Field:                    Size in packet
        $payload = "\x01\x38" . // transaction id: 0138 (2 bytes)
            "\x00\x00" . // protocol id: 0000           (2 bytes)
            "\x00\x0f" .  // length: 000f               (2 bytes) (15 bytes after this field)
            "\x11" . // unit id: 11                     (1 byte)
            "\x17" . // function code: 17               (1 byte)
            "\x04\x10" . // read start address: 0410    (2 bytes)
            "\x00\x01" . // read quantity: 1            (2 bytes)
            "\x01\x12" . // write start address: 0112   (2 bytes)
            "\x00\x02" . // write quantity: 2           (2 bytes)
            "\x04" . // registersBytesSize: 4           (1 byte)
            "\x00\xc8\x00\x82" . // data: 00c8 0082     (2 registers = 4 bytes)
            '';
        $packet = ReadWriteMultipleRegistersRequest::parse($payload);
        $this->assertEquals($packet, (new ReadWriteMultipleRegistersRequest(
            0x0410,
            1,
            0x0112,
            [Types::toByte(200), Types::toInt16(130, Endian::BIG_ENDIAN_LOW_WORD_FIRST)],
            0x11,
            0x0138
        ))->__toString());
        $this->assertEquals(0x0410, $packet->getStartAddress());
        $this->assertEquals(2, $packet->getWriteRegisterCount());
        $this->assertEquals(0x0112, $packet->getWriteStartAddress());
        $this->assertEquals(["\x00\xC8", "\x00\x82"], $packet->getRegisters());

        $header = $packet->getHeader();
        $this->assertEquals(0x0138, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(15, $header->getLength());
        $this->assertEquals(0x11, $header->getUnitId());
    }

    public function testParseShouldReturnErrorResponseForTooShortPacket()
    {
        // Field:                    Size in packet
        $payload = "\x01\x38" . // transaction id: 0138 (2 bytes)
            "\x00\x00" . // protocol id: 0000           (2 bytes)
            "\x00\x0f" .  // length: 000f               (2 bytes) (15 bytes after this field)
            "\x11" . // unit id: 11                     (1 byte)
            "\x17" . // function code: 17               (1 byte)
            "\x04\x10" . // read start address: 0410    (2 bytes)
            "\x00\x01" . // read quantity: 1            (2 bytes)
            "\x01\x12" . // write start address: 0112   (2 bytes)
            "\x00\x02" . // write quantity: 2           (2 bytes)
            "\x04" . // registersBytesSize: 4           (1 byte)
            "\x00" . // shorter than expected (4)
            '';
        $packet = ReadWriteMultipleRegistersRequest::parse($payload);
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        // transaction id is random
        $toString[0] = "\x00";
        $toString[1] = "\x00";
        self::assertEquals("\x00\x00\x00\x00\x00\x03\x00\x97\x04", $toString);
    }

    public function testParseShouldReturnErrorResponseForTooShortPacketByByteCount()
    {
        // Field:                    Size in packet
        $payload = "\x01\x38" . // transaction id: 0138 (2 bytes)
            "\x00\x00" . // protocol id: 0000           (2 bytes)
            "\x00\x0f" .  // length: 000f               (2 bytes) (15 bytes after this field)
            "\x11" . // unit id: 11                     (1 byte)
            "\x17" . // function code: 17               (1 byte)
            "\x04\x10" . // read start address: 0410    (2 bytes)
            "\x00\x01" . // read quantity: 1            (2 bytes)
            "\x01\x12" . // write start address: 0112   (2 bytes)
            "\x00\x01" . // write quantity: 1           (2 bytes) <-- should be 2 but is 1
            "\x04" . // registersBytesSize: 4           (1 byte)
            "\x00\xc8\x00\x82" . // data: 00c8 0082     (2 registers = 4 bytes)
            '';
        $packet = ReadWriteMultipleRegistersRequest::parse($payload);
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        // transaction id is random
        $toString[0] = "\x00";
        $toString[1] = "\x00";
        self::assertEquals("\x00\x00\x00\x00\x00\x03\x11\x97\x03", $toString);
    }

    public function testParseShouldReturnErrorResponseForInvalidFunction()
    {
        // Field:                    Size in packet
        $payload = "\x01\x38" . // transaction id: 0138 (2 bytes)
            "\x00\x00" . // protocol id: 0000           (2 bytes)
            "\x00\x0f" .  // length: 000f               (2 bytes) (15 bytes after this field)
            "\x11" . // unit id: 11                     (1 byte)
            "\x00" . // function code: 00               (1 byte) <-- should be 17 but is 00
            "\x04\x10" . // read start address: 0410    (2 bytes)
            "\x00\x01" . // read quantity: 1            (2 bytes)
            "\x01\x12" . // write start address: 0112   (2 bytes)
            "\x00\x01" . // write quantity: 1           (2 bytes) <-- should be 2 but is 1
            "\x04" . // registersBytesSize: 4           (1 byte)
            "\x00\xc8\x00\x82" . // data: 00c8 0082     (2 registers = 4 bytes)
            '';
        $packet = ReadWriteMultipleRegistersRequest::parse($payload);
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        self::assertEquals("\x01\x38\x00\x00\x00\x03\x11\x97\x01", $toString);
    }
}
