<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputDiscretesResponse;
use PHPUnit\Framework\TestCase;

class ReadInputDiscretesResponseTest extends TestCase
{
    public function testPacketToString()
    {
        $this->assertEquals(
            "\x81\x80\x00\x00\x00\x05\x03\x02\x02\xCD\x6B",
            (new ReadInputDiscretesResponse("\xCD\x6B", 3, 33152))->__toString()
        );
    }

    public function testPacketProperties()
    {
        $packet = new ReadInputDiscretesResponse("\xCD\x6B", 3, 33152);
        $this->assertEquals(IModbusPacket::READ_INPUT_DISCRETES, $packet->getFunctionCode());

        $this->assertEquals("\xCD\x6B", $packet->getRawData());
        $this->assertEquals([0xCD, 0x6B], $packet->getData()); //TODO data as boolean array?

        $header = $packet->getHeader();
        $this->assertEquals(33152, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(5, $header->getLength());
        $this->assertEquals(3, $header->getUnitId());
    }

}