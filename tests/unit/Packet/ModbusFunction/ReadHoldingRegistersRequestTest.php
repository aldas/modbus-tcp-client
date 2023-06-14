<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Endian;
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

class ReadHoldingRegistersRequestTest extends TestCase
{
    protected function setUp(): void
    {
        Endian::$defaultEndian = Endian::LITTLE_ENDIAN; // packets are big endian. setting to default to little should not change output
    }

    protected function tearDown(): void
    {
        Endian::$defaultEndian = Endian::BIG_ENDIAN_LOW_WORD_FIRST;
    }

    public function testPacketToString()
    {
        $this->assertEquals(
            "\x00\x01\x00\x00\x00\x06\x11\x03\x00\x6B\x00\x03",
            (new ReadHoldingRegistersRequest(107, 3, 17, 1))->__toString()
        );
    }

    public function testPacketProperties()
    {
        $packet = new ReadHoldingRegistersRequest(107, 3, 17, 1);
        $this->assertEquals(ModbusPacket::READ_HOLDING_REGISTERS, $packet->getFunctionCode());
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
        $packet = new ReadHoldingRegistersRequest(107, 3);

        $this->assertEquals(ModbusPacket::READ_HOLDING_REGISTERS, $packet->getFunctionCode());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals(3, $packet->getQuantity());

        $header = $packet->getHeader();
        $this->assertNotNull($header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(0, $header->getUnitId());
    }

    public function testShouldThrowExceptionOnNullQuantity()
    {
        $this->expectException(\TypeError::class);

        new ReadHoldingRegistersRequest(107, null);
    }

    public function testShouldThrowExceptionOnBelowLimitQuantity()
    {
        $this->expectExceptionMessage("quantity is not set or out of range (0-124):");
        $this->expectException(InvalidArgumentException::class);

        new ReadHoldingRegistersRequest(107, 0);
    }

    public function testShouldThrowExceptionOnOverLimitQuantity()
    {
        $this->expectExceptionMessage("quantity is not set or out of range (0-124):");
        $this->expectException(InvalidArgumentException::class);

        new ReadHoldingRegistersRequest(107, 125);
    }

    public function testParse()
    {
        $packet = ReadHoldingRegistersRequest::parse("\x00\x01\x00\x00\x00\x06\x11\x03\x00\x6B\x00\x03");
        $this->assertEquals($packet, (new ReadHoldingRegistersRequest(107, 3, 17, 1))->__toString());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals(3, $packet->getQuantity());

        $header = $packet->getHeader();
        $this->assertEquals(1, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(17, $header->getUnitId());
    }

    public function testParseShouldReturnErrorResponseForTooShortPacket()
    {
        $packet = ReadHoldingRegistersRequest::parse("\x00\x01\x00\x00\x00\x06\x11\x03\x00\x6B\x00");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        // transaction id is random
        $toString[0] = "\x00";
        $toString[1] = "\x00";
        self::assertEquals("\x00\x00\x00\x00\x00\x03\x00\x83\x04", $toString);
    }

    public function testParseShouldReturnErrorResponseForInvalidFunction()
    {
        $packet = ReadHoldingRegistersRequest::parse("\x00\x01\x00\x00\x00\x06\x11\x04\x00\x6B\x00\x01");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        self::assertEquals("\x00\x01\x00\x00\x00\x03\x11\x83\x01", $toString);
    }

    public function testParseShouldReturnErrorResponseForInvalidQuantity()
    {
        $packet = ReadHoldingRegistersRequest::parse("\x00\x01\x00\x00\x00\x06\x11\x03\x00\x6B\x00\x00");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        self::assertEquals("\x00\x01\x00\x00\x00\x03\x11\x83\x03", $toString);
    }

}
