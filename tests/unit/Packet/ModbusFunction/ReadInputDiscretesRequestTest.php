<?php
namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputDiscretesRequest;
use PHPUnit\Framework\TestCase;

class ReadInputDiscretesRequestTest extends TestCase
{
    public function testPacketToString()
    {
        $this->assertEquals(
            "\x00\x01\x00\x00\x00\x06\x10\x02\x00\x6B\x00\x03",
            (new ReadInputDiscretesRequest(107, 3, 16, 1))->__toString()
        );
    }

    public function testPacketProperties()
    {
        $packet = new ReadInputDiscretesRequest(107, 3, 17, 1);
        $this->assertEquals(ModbusPacket::READ_INPUT_DISCRETES, $packet->getFunctionCode());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals(3, $packet->getQuantity());

        $header = $packet->getHeader();
        $this->assertEquals(1, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(17, $header->getUnitId());
    }

}