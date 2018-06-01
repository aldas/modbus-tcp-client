<?php

namespace Tests\unit\Packet;


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

    public function testGetRandomTransactionId()
    {
        $header = new ModbusApplicationHeader(5, 17);

        $this->assertInternalType('int', $header->getTransactionId());
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

    /**
     * @expectedException \ModbusTcpClient\Exception\ModbusException
     * @expectedExceptionMessage Data length too short to be valid header!
     */
    public function testParseGarbage()
    {
        ModbusApplicationHeader::parse("\x00\x01\x00\x00\x00\x06");
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage length is not set or out of range (uint16): 0
     */
    public function testLengthUnderFlow()
    {
        new ModbusApplicationHeader(0, 17, 1);
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage length is not set or out of range (uint16): 65536
     */
    public function testLengthOverFlow()
    {
        new ModbusApplicationHeader(65536, 17, 1);
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage unitId is out of range (0-247): 248
     */
    public function testUnitIdOverFlow()
    {
        new ModbusApplicationHeader(1, 248, 1);
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage unitId is out of range (0-247): -1
     */
    public function testUnitIdUnderFlow()
    {
        new ModbusApplicationHeader(1, -1, 1);
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage transactionId is out of range (uint16): -1
     */
    public function testTransactionIdUnderFlow()
    {
        new ModbusApplicationHeader(1, 0, -1);
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage transactionId is out of range (uint16): 65536
     */
    public function testTransactionIdOverFlow()
    {
        new ModbusApplicationHeader(1, 0, 65536);
    }

}