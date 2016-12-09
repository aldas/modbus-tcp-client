<?php
namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegisters;
use PHPUnit\Framework\TestCase;

/*
 * Source: http://www.simplymodbus.ca/TCP.htm
 *
 * Here is an example of a Modbus RTU request for the content of analog output holding registers # 40108 to 40110.
 * 0001 0000 0006 11 03 006B 0003
 *
 * 0001: Transaction Identifier
 * 0000: Protocol Identifier
 * 0006: Message Length (6 bytes to follow)
 * 11: The Unit Identifier  (17 = 11 hex)
 * 03: The Function Code (read Analog Output Holding Registers)
 * 006B: The Data Address of the first register requested. (40108-40001 = 107 =6B hex)
 * 0003: The total number of registers requested. (read 3 registers 40108 to 40110)
 */

class ReadHoldingRegistersTest extends TestCase
{
    public function testPacketToString()
    {
        $this->assertEquals(
            "\x00\x01\x00\x00\x00\x06\x11\x03\x00\x6B\x00\x03",
            (new ReadHoldingRegisters(107, 3, 17, 1))->__toString()
        );
    }

    public function testPacketProperties()
    {
        $packet = new ReadHoldingRegisters(107, 3, 17, 1);
        $this->assertEquals(3, $packet->getFunctionCode());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals(3, $packet->getQuantity());

        $header = $packet->getHeader();
        $this->assertEquals(1, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(17, $header->getUnitId());
    }

    public function testPacketMandatoryProperties()
    {
        $packet = new ReadHoldingRegisters(107, 3);

        $this->assertEquals(3, $packet->getFunctionCode());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals(3, $packet->getQuantity());

        $header = $packet->getHeader();
        $this->assertNotNull($header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(0, $header->getUnitId());
    }

}