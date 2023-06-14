<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\MaskWriteRegisterRequest;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\Word;
use ModbusTcpClient\Utils\Endian;
use ModbusTcpClient\Utils\Types;
use PHPUnit\Framework\TestCase;

class MaskWriteRegisterRequestTest extends TestCase
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
            "\x00\x08" .  // length: 0008               (2 bytes) (8 bytes after this field)
            "\x11" . // unit id: 11                     (1 byte)
            "\x16" . // function code: 16               (1 byte)
            "\x04\x10" . // start address: 0410         (2 bytes)
            "\x00\x01" . // AND mask: 0x01              (2 bytes)
            "\x00\x02" . // OR mask: 0x02               (2 bytes)
            '';
        $this->assertEquals(
            $payload,
            (new MaskWriteRegisterRequest(
                0x0410,
                0x1,
                0x2,
                0x11,
                0x0138
            ))->__toString()
        );
    }

    public function testValidateANDMaskUnderflow()
    {
        $this->expectExceptionMessage("AND mask is out of range (u)int16: -32769");
        $this->expectException(InvalidArgumentException::class);

        (new MaskWriteRegisterRequest(
            0x0410,
            Types::MIN_VALUE_INT16 - 1,
            0x2,
        ))->__toString();
    }

    public function testValidateANDMaskOverflow()
    {
        $this->expectExceptionMessage("AND mask is out of range (u)int16: 65536");
        $this->expectException(InvalidArgumentException::class);

        (new MaskWriteRegisterRequest(
            0x0410,
            Types::MAX_VALUE_UINT16 + 1,
            0x2,
        ))->__toString();
    }

    public function testValidateORMaskOverflow()
    {
        $this->expectExceptionMessage("OR mask is out of range (u)int16: 65536");
        $this->expectException(InvalidArgumentException::class);

        (new MaskWriteRegisterRequest(
            0x0410,
            0,
            Types::MAX_VALUE_UINT16 + 1,
        ))->__toString();
    }

    public function testValidateORMaskUnderflow()
    {
        $this->expectExceptionMessage("OR mask is out of range (u)int16: -32769");
        $this->expectException(InvalidArgumentException::class);

        (new MaskWriteRegisterRequest(
            0x0410,
            0,
            Types::MIN_VALUE_INT16 - 1,
        ))->__toString();
    }


    public function testValidateReadStartAddressUnderflow()
    {
        $this->expectExceptionMessage("startAddress is out of range: -1");
        $this->expectException(InvalidArgumentException::class);

        (new MaskWriteRegisterRequest(
            -1,
            0,
            0,
        ))->__toString();
    }

    public function testValidateReadStartAddressOverflow()
    {
        $this->expectExceptionMessage("startAddress is out of range: 65536");
        $this->expectException(InvalidArgumentException::class);

        (new MaskWriteRegisterRequest(
            Types::MAX_VALUE_UINT16 + 1,
            0,
            0,
        ))->__toString();
    }

    public function testOnPacketProperties()
    {
        $packet = new MaskWriteRegisterRequest(
            0x0410,
            0x1,
            0x2,
            0x11,
            0x0138
        );
        $this->assertEquals(ModbusPacket::MASK_WRITE_REGISTER, $packet->getFunctionCode());
        $this->assertEquals(0x0410, $packet->getStartAddress());

        $this->assertEquals(0x1, $packet->getANDMask());
        $this->assertEquals(0x2, $packet->getORMask());

        $this->assertEquals(new Word("\x00\x01"), $packet->getANDMaskAsWord());
        $this->assertEquals(new Word("\x00\x02"), $packet->getORMaskAsWord());

        $header = $packet->getHeader();
        $this->assertEquals(0x0138, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(8, $header->getLength());
        $this->assertEquals(0x11, $header->getUnitId());
    }

    public function testParse()
    {
        // Field:                    Size in packet
        $payload = "\x01\x38" . // transaction id: 0138 (2 bytes)
            "\x00\x00" . // protocol id: 0000           (2 bytes)
            "\x00\x08" .  // length: 0008               (2 bytes) (8 bytes after this field)
            "\x11" . // unit id: 11                     (1 byte)
            "\x16" . // function code: 16               (1 byte)
            "\x04\x10" . // start address: 0410         (2 bytes)
            "\x00\x01" . // AND mask: 0x01              (2 bytes)
            "\x00\x02" . // OR mask: 0x02               (2 bytes)
            '';
        $packet = MaskWriteRegisterRequest::parse($payload);
        $this->assertEquals($packet, (new MaskWriteRegisterRequest(
            0x0410,
            0x1,
            0x2,
            0x11,
            0x0138
        ))->__toString());
        $this->assertEquals(ModbusPacket::MASK_WRITE_REGISTER, $packet->getFunctionCode());
        $this->assertEquals(0x0410, $packet->getStartAddress());

        $this->assertEquals(0x1, $packet->getANDMask());
        $this->assertEquals(0x2, $packet->getORMask());

        $this->assertEquals(new Word("\x00\x01"), $packet->getANDMaskAsWord());
        $this->assertEquals(new Word("\x00\x02"), $packet->getORMaskAsWord());

        $header = $packet->getHeader();
        $this->assertEquals(0x0138, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(8, $header->getLength());
        $this->assertEquals(0x11, $header->getUnitId());
    }

    public function testParseShouldReturnErrorResponseForTooShortPacket()
    {
        // Field:                    Size in packet
        $payload = "\x01\x38" . // transaction id: 0138 (2 bytes)
            "\x00\x00" . // protocol id: 0000           (2 bytes)
            "\x00\x07" .  // length: 0007               (2 bytes) (7 bytes after this field)
            "\x11" . // unit id: 11                     (1 byte)
            "\x16" . // function code: 16               (1 byte)
            "\x04\x10" . // start address: 0410         (2 bytes)
            "\x00\x01" . // AND mask: 0x01              (2 bytes)
            "\x00" . // OR mask: 0x02               (1 bytes) <-- should be 2 but is 1
            '';
        $packet = MaskWriteRegisterRequest::parse($payload);
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        // transaction id is random
        $toString[0] = "\x00";
        $toString[1] = "\x00";
        self::assertEquals("\x00\x00\x00\x00\x00\x03\x00\x96\x04", $toString);
    }

    public function testParseShouldReturnErrorResponseForInvalidFunction()
    {
        // Field:                    Size in packet
        $payload = "\x01\x38" . // transaction id: 0138 (2 bytes)
            "\x00\x00" . // protocol id: 0000           (2 bytes)
            "\x00\x08" .  // length: 0008               (2 bytes) (8 bytes after this field)
            "\x11" . // unit id: 11                     (1 byte)
            "\x17" . // function code: 17               (1 byte) <-- should be 0x16
            "\x04\x10" . // start address: 0410         (2 bytes)
            "\x00\x01" . // AND mask: 0x01              (2 bytes)
            "\x00\x02" . // OR mask: 0x02               (2 bytes)
            '';
        $packet = MaskWriteRegisterRequest::parse($payload);
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        self::assertEquals("\x01\x38\x00\x00\x00\x03\x11\x96\x01", $toString);
    }
}
