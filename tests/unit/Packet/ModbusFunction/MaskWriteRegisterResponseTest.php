<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusFunction\MaskWriteRegisterResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\Word;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class MaskWriteRegisterResponseTest extends TestCase
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
            (new MaskWriteRegisterResponse(
                "\x04\x10\x00\x01\x00\x02",
                0x11,
                0x0138,
                0x11,
                0x0138
            ))->__toString()
        );
    }

    public function testOnPacketProperties()
    {
        $packet = new MaskWriteRegisterResponse(
            "\x04\x10\x00\x01\x00\x02",
            0x11,
            0x0138,
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
}
