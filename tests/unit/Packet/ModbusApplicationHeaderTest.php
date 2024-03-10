<?php

namespace Tests\unit\Packet;


use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Packet\ModbusApplicationHeader;
use PHPUnit\Framework\TestCase;

class ModbusApplicationHeaderTest extends TestCase
{
    public function testToString()
    {
        $header = new ModbusApplicationHeader(5, 17, 1);

        $this->assertEquals(
            "\x00\x01\x00\x00\x00\x06\x11",
            $header->__toString()
        );
    }

    public function testGetTransactionId()
    {
        $header = new ModbusApplicationHeader(5, 17, 1);

        $this->assertEquals(1, $header->getTransactionId());
    }

    public function testGetProtocolId()
    {
        $header = new ModbusApplicationHeader(5, 17, 1);

        $this->assertEquals(0, $header->getProtocolId());
    }

    public function testGetLength()
    {
        $header = new ModbusApplicationHeader(5, 17, 1);

        $this->assertEquals(6, $header->getLength());
    }

    public function testGetUnitId()
    {
        $header = new ModbusApplicationHeader(5, 17, 1);

        $this->assertEquals(17, $header->getUnitId());
    }

    public function testParse()
    {
        $this->assertEquals(
            new ModbusApplicationHeader(6, 17, 1),
            ModbusApplicationHeader::parse("\x00\x01\x00\x00\x00\x06\x11\x03\x00\x6B\x00\x03")
        );
    }

    public function testParseGarbage()
    {
        $this->expectExceptionMessage("Data length too short to be valid header!");
        $this->expectException(ModbusException::class);

        ModbusApplicationHeader::parse("\x00\x01\x00\x00\x00\x06");
    }

    public function testLengthUnderFlow()
    {
        $this->expectExceptionMessage("length is not set or out of range (uint16): 0");
        $this->expectException(InvalidArgumentException::class);

        new ModbusApplicationHeader(0, 17, 1);
    }

    public function testLengthOverFlow()
    {
        $this->expectExceptionMessage("length is not set or out of range (uint16): 65536");
        $this->expectException(InvalidArgumentException::class);

        new ModbusApplicationHeader(65536, 17, 1);
    }

    public function testUnitIdOverFlow()
    {
        $this->expectExceptionMessage("unitId is out of range (0-255): 256");
        $this->expectException(InvalidArgumentException::class);

        new ModbusApplicationHeader(1, 256, 1);
    }

    public function testUnitIdUnderFlow()
    {
        $this->expectExceptionMessage("unitId is out of range (0-255): -1");
        $this->expectException(InvalidArgumentException::class);

        new ModbusApplicationHeader(1, -1, 1);
    }

    public function testTransactionIdUnderFlow()
    {
        $this->expectExceptionMessage("transactionId is out of range (uint16): -1");
        $this->expectException(InvalidArgumentException::class);

        new ModbusApplicationHeader(1, 0, -1);
    }

    public function testTransactionIdOverFlow()
    {
        $this->expectExceptionMessage("transactionId is out of range (uint16): 65536");
        $this->expectException(InvalidArgumentException::class);

        new ModbusApplicationHeader(1, 0, 65536);
    }

}
