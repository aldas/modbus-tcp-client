<?php
namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleRegisterRequest;
use PHPUnit\Framework\TestCase;

class WriteSingleRegisterRequestTest extends TestCase
{
    public function testPacketToString()
    {
        $this->assertEquals(
            "\x00\x01\x00\x00\x00\x06\x11\x06\x00\x6B\x01\x01",
            (new WriteSingleRegisterRequest(107, 257, 17, 1))->__toString()
        );
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage value is not set or out of range (int16): 213213123
     */
    public function testValueValidationException()
    {
        $this->assertEquals(
            "\x00\x01\x00\x00\x00\x06\x11\x06\x00\x6B\x01\x01",
            (new WriteSingleRegisterRequest(107, 213213123, 17, 1))->__toString()
        );
    }

    public function testValueValidationValidForNegative1()
    {
        $this->assertEquals(
            "\x00\x01\x00\x00\x00\x06\x11\x06\x00\x6B\xFF\xFF",
            (new WriteSingleRegisterRequest(107, -1, 17, 1))->__toString()
        );
    }


    public function testPacketProperties()
    {
        $packet = new WriteSingleRegisterRequest(107, 257, 17, 1);
        $this->assertEquals(ModbusPacket::WRITE_SINGLE_REGISTER, $packet->getFunctionCode());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals(257, $packet->getValue());

        $header = $packet->getHeader();
        $this->assertEquals(1, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(17, $header->getUnitId());
    }

}