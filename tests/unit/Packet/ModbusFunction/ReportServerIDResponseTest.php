<?php

namespace Tests\unit\Packet\ModbusFunction;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ModbusFunction\ReportServerIDResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class ReportServerIDResponseTest extends TestCase
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
            "\x11" . // function code: 0b               (1 byte)
            "\x02" . // server id byte count            (1 bytes)
            "\x01\x02" . // server id (0x0102)          (N bytes)
            "\xFF" . // status: FF                      (1 bytes)
            "\x03\x04" . // additional data (           (optionally N bytes)
            '';
        $this->assertEquals(
            $payload,
            (new ReportServerIDResponse(
                "\x02\x01\x02\xFF\x03\x04",
                0x11,
                0x0138,
            ))->__toString()
        );
    }

    public function testOnPacketToStringWithoutAdditionalData()
    {
        // Field:                    Size in packet
        $payload = "\x01\x38" . // transaction id: 0138 (2 bytes)
            "\x00\x00" . // protocol id: 0000           (2 bytes)
            "\x00\x06" .  // length: 0006               (2 bytes) (6 bytes after this field)
            "\x11" . // unit id: 11                     (1 byte)
            "\x11" . // function code: 0b               (1 byte)
            "\x02" . // server id byte count            (1 bytes)
            "\x01\x02" . // server id (0x0102)          (N bytes)
            "\xFF" . // status: FF                      (1 bytes)
            ''; // no additional data
        $this->assertEquals(
            $payload,
            (new ReportServerIDResponse(
                "\x02\x01\x02\xFF",
                0x11,
                0x0138,
            ))->__toString()
        );
    }

    public function testValidateServerIDbytesLengthTooShort()
    {
        $this->expectExceptionMessage("too few bytes to be a complete report server id packet");
        $this->expectException(InvalidArgumentException::class);

        new ReportServerIDResponse(
            "\x03\x01\x02\xFF",
            0x11,
            0x0138,
        );
    }

    public function testOnPacketProperties()
    {
        $packet = new ReportServerIDResponse(
            "\x02\x01\x02\xFF\x03\x04",
            0x11,
            0x0138,
        );
        $this->assertEquals(ModbusPacket::REPORT_SERVER_ID, $packet->getFunctionCode());

        $this->assertEquals("\x01\x02", $packet->getServerID());
        $this->assertEquals([0x01, 0x02], $packet->getServerIDBytes());

        $this->assertEquals(0xFF, $packet->getStatus());

        $this->assertEquals("\x03\x04", $packet->getAdditionalData());
        $this->assertEquals([0x03, 0x04], $packet->getAdditionalDataBytes());

        $header = $packet->getHeader();
        $this->assertEquals(0x0138, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(8, $header->getLength());
        $this->assertEquals(0x11, $header->getUnitId());
    }

    public function testOnPacketPropertiesWithoutAdditionalData()
    {
        $packet = new ReportServerIDResponse(
            "\x02\x01\x02\xFF",
            0x11,
            0x0138,
        );
        $this->assertEquals(ModbusPacket::REPORT_SERVER_ID, $packet->getFunctionCode());

        $this->assertEquals("\x01\x02", $packet->getServerID());
        $this->assertEquals([0x01, 0x02], $packet->getServerIDBytes());

        $this->assertEquals(0xFF, $packet->getStatus());

        $this->assertEquals(null, $packet->getAdditionalData());
        $this->assertEquals([], $packet->getAdditionalDataBytes());

        $header = $packet->getHeader();
        $this->assertEquals(0x0138, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(0x11, $header->getUnitId());
    }

    public function testWithStartAddress()
    {
        $packet = new ReportServerIDResponse(
            "\x02\x01\x02\xFF\x03\x04",
            0x11,
            0x0138,
        );
        $packetWithStartAddress = $packet->withStartAddress(1);

        $this->assertEquals(ModbusPacket::REPORT_SERVER_ID, $packetWithStartAddress->getFunctionCode());
        $this->assertEquals("\x01\x02", $packet->getServerID());
        $this->assertEquals([0x01, 0x02], $packet->getServerIDBytes());

        $this->assertEquals(0xFF, $packet->getStatus());

        $this->assertEquals("\x03\x04", $packet->getAdditionalData());
        $this->assertEquals([0x03, 0x04], $packet->getAdditionalDataBytes());


        $header = $packetWithStartAddress->getHeader();
        $this->assertEquals(0x0138, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(8, $header->getLength());
        $this->assertEquals(0x11, $header->getUnitId());
    }
}
