<?php
namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleCoilsRequest;
use PHPUnit\Framework\TestCase;

class WriteMultipleCoilsRequestTest extends TestCase
{
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

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage coils count out of range (1-2048): 0
     */
    public function testValidateEmptyCoils()
    {
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
}