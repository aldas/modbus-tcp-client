<?php
namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsRequest;
use ModbusTcpClient\Utils\Types;
use PHPUnit\Framework\TestCase;

class ReadCoilsRequestTest extends TestCase
{
    public function testPacketToString()
    {
        $this->assertEquals(
            "\x00\x01\x00\x00\x00\x06\x10\x01\x00\x6B\x00\x03",
            (new ReadCoilsRequest(107, 3, 16, 1))->__toString()
        );
    }

    public function testPacketProperties()
    {
        $packet = new ReadCoilsRequest(107, 3, 17, 1);
        $this->assertEquals(ModbusPacket::READ_COILS, $packet->getFunctionCode());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals(3, $packet->getQuantity());

        $header = $packet->getHeader();
        $this->assertEquals(1, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(17, $header->getUnitId());
    }

    /**
     * @expectedException \TypeError
     */
    public function testShouldThrowExceptionOnNullQuantity()
    {
        new ReadCoilsRequest(107, null, 17, 1);
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage quantity is not set or out of range (1-2048):
     */
    public function testShouldThrowExceptionOnBelowLimitQuantity()
    {
        new ReadCoilsRequest(107, 0, 17, 1);
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage quantity is not set or out of range (1-2048):
     */
    public function testShouldThrowExceptionOnOverLimitQuantity()
    {
        new ReadCoilsRequest(107, 256*8 + 1, 17, 1);
    }
}