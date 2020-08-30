<?php
namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Exception\InvalidArgumentException;
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

    public function testShouldThrowExceptionOnNullQuantity()
    {
        $this->expectException(\TypeError::class);

        new ReadCoilsRequest(107, null, 17, 1);
    }

    public function testShouldThrowExceptionOnBelowLimitQuantity()
    {
        $this->expectExceptionMessage("quantity is not set or out of range (1-2048):");
        $this->expectException(InvalidArgumentException::class);

        new ReadCoilsRequest(107, 0, 17, 1);
    }

    public function testShouldThrowExceptionOnOverLimitQuantity()
    {
        $this->expectExceptionMessage("quantity is not set or out of range (1-2048):");
        $this->expectException(InvalidArgumentException::class);

        new ReadCoilsRequest(107, 256*8 + 1, 17, 1);
    }
}
