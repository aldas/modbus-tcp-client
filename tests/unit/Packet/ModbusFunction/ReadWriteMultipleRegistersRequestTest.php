<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusFunction\ReadWriteMultipleRegistersRequest;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Types;
use PHPUnit\Framework\TestCase;

class ReadWriteMultipleRegistersRequestTest extends TestCase
{
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
                [Types::toByte(200), Types::toInt16(130)],
                0x11,
                0x0138
            ))->__toString()
        );
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage write registers count out of range (1-121): 0
     */
    public function testValidateEmptyWriteRegisters()
    {
        (new ReadWriteMultipleRegistersRequest(
            0x0410,
            1,
            0x0112,
            []
        ))->__toString();
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage read registers quantity out of range (1-125): 0
     */
    public function testValidateReadQuantityUnderflow()
    {
        (new ReadWriteMultipleRegistersRequest(
            0x0410,
            0,
            0x0112,
            [Types::toByte(200)]
        ))->__toString();
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage read registers quantity out of range (1-125): 126
     */
    public function testValidateReadQuantityOverflow()
    {
        (new ReadWriteMultipleRegistersRequest(
            0x0410,
            126,
            0x0112,
            [Types::toByte(200)]
        ))->__toString();
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage startAddress is not set or out of range: -1
     */
    public function testValidateReadStartAddressUnderflow()
    {
        (new ReadWriteMultipleRegistersRequest(
            -1,
            1,
            0x0112,
            [Types::toByte(200)]
        ))->__toString();
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage startAddress is not set or out of range: 65536
     */
    public function testValidateReadStartAddressOverflow()
    {
        (new ReadWriteMultipleRegistersRequest(
            Types::MAX_VALUE_UINT16 + 1,
            1,
            0x0112,
            [Types::toByte(200)]
        ))->__toString();
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage write registers start address out of range (0-65535): -1
     */
    public function testValidateWriteStartAddressUnderflow()
    {
        (new ReadWriteMultipleRegistersRequest(
            1,
            1,
            -1,
            [Types::toByte(200)]
        ))->__toString();
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage write registers start address out of range (0-65535): 65536
     */
    public function testValidateWriteStartAddressOverflow()
    {
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
}
