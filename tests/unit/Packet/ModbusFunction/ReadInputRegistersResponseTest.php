<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use PHPUnit\Framework\TestCase;

class ReadInputRegistersResponseTest extends TestCase
{
    public function testPacketToString()
    {
        $this->assertEquals(
            "\x81\x80\x00\x00\x00\x05\x03\x04\x02\xCD\x6B",
            (new ReadInputRegistersResponse("\x02\xCD\x6B", 3, 33152))->__toString()
        );
    }

    public function testPacketProperties()
    {
        $packet = new ReadInputRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01", 3, 33152);
        $this->assertEquals(ModbusPacket::READ_INPUT_REGISTERS, $packet->getFunctionCode());

        $this->assertEquals([0xCD, 0x6B, 0x0, 0x0, 0x0, 0x1], $packet->getData());

        $header = $packet->getHeader();
        $this->assertEquals(33152, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(9, $header->getLength());
        $this->assertEquals(3, $header->getUnitId());
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ParseException
     * @expectedExceptionMessage packet byte count does not match bytes in packet! count: 3, actual: 2
     */
    public function testFailWhenByteCountDoesNotMatch()
    {
        new ReadInputRegistersResponse("\x03\xCD\x6B", 3, 33152);
    }

}