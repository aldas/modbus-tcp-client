<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusFunction\ReadInputDiscretesResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use PHPUnit\Framework\TestCase;

class ReadInputDiscretesResponseTest extends TestCase
{
    public function testPacketToString()
    {
        $this->assertEquals(
            "\x81\x80\x00\x00\x00\x05\x03\x02\x02\xCD\x6B",
            (new ReadInputDiscretesResponse("\x02\xCD\x6B", 3, 33152))->__toString()
        );
    }

    public function testPacketProperties()
    {
        $packet = new ReadInputDiscretesResponse("\x02\xCD\x6B", 3, 33152);
        $this->assertEquals(ModbusPacket::READ_INPUT_DISCRETES, $packet->getFunctionCode());

        $this->assertEquals([
            1, 0, 1, 1, 0, 0, 1, 1,  // hex: CD -> bin: 1100 1101 -> reverse for user input: 1011 0011
            1, 1, 0, 1, 0, 1, 1, 0   // hex: 6B -> bin: 0110 1011 -> reverse for user input: 1101 0110
        ], $packet->getCoils());

        $header = $packet->getHeader();
        $this->assertEquals(33152, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(5, $header->getLength());
        $this->assertEquals(3, $header->getUnitId());
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ParseException
     * @expectedExceptionMessage packet byte count does not match bytes in packet! count: 3, actual: 2
     */
    public function testFailWhenByteCountDoesNotMatch()
    {
        new ReadInputDiscretesResponse("\x03\xCD\x6B", 3, 33152);
    }

}