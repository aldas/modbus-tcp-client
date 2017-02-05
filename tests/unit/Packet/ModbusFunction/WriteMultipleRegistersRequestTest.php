<?php
namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleRegistersRequest;
use ModbusTcpClient\Utils\Types;
use PHPUnit\Framework\TestCase;

class WriteMultipleRegistersRequestTest extends TestCase
{
    public function testPacketToString()
    {
        $this->assertEquals(
            "\x01\x38\x00\x00\x00\x0b\x11\x10\x04\x10\x00\x03\x06\x00\xC8\x00\x82\x87\x01",
            (new WriteMultipleRegistersRequest(0x0410, [Types::toByte(200), Types::toUInt16BE(130), Types::toUInt16BE(34561)], 0x11, 0x0138))->__toString()
        );
    }

    /**
     * @expectedException \OutOfRangeException
     * @expectedExceptionMessage registers count out of range (1-124): 0
     */
    public function testValidateEmptyRegistersThrowsException()
    {
        (new WriteMultipleRegistersRequest(107, [], 17, 1))->__toString();
    }

    public function testPacketProperties()
    {
        $registers = [Types::toByte(200), Types::toUInt16BE(130), Types::toUInt16BE(34561)];
        $packet = new WriteMultipleRegistersRequest(107, $registers, 17, 1);
        $this->assertEquals(IModbusPacket::WRITE_MULTIPLE_REGISTERS, $packet->getFunctionCode());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals($registers, $packet->getRegisters());

        $header = $packet->getHeader();
        $this->assertEquals(1, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(11, $header->getLength());
        $this->assertEquals(17, $header->getUnitId());
    }
}