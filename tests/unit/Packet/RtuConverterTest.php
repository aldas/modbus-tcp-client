<?php

namespace Tests\unit\Packet;


use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusApplicationHeader;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersResponse;
use ModbusTcpClient\Packet\RtuConverter;
use PHPUnit\Framework\TestCase;

class RtuConverterTest extends TestCase
{
    public function testPacketToRtu()
    {
        $tcpPacket = new ReadHoldingRegistersRequest(107, 3, 17, 1);

        $rtuBinary = RtuConverter::toRtu($tcpPacket);

        $this->assertEquals("\x11\x03\x00\x6B\x00\x03\x76\x87", $rtuBinary);
    }

    public function testPacketfromRtu()
    {
        /** @var ReadHoldingRegistersRequest $packet */
        $packet = RtuConverter::fromRtu("\x03\x03\x02\xCD\x6B\xD4\xFB");

        $this->assertInstanceOf(ReadHoldingRegistersResponse::class, $packet);

        $tcpPacket = new ReadHoldingRegistersResponse("\x02\xCD\x6B", 3, $packet->getHeader()->getTransactionId());
        $this->assertEquals($packet, $tcpPacket);
    }

    public function testExceptionPacketfromRtu()
    {
        /** @var ErrorResponse $packet */
        $packet = RtuConverter::fromRtu("\x00\x81\x03\x51\x91");

        $this->assertInstanceOf(ErrorResponse::class, $packet);

        $tcpPacket = new ErrorResponse(
            new ModbusApplicationHeader(3, 0, $packet->getHeader()->getTransactionId())
            , 1
            , 3
        );
        $this->assertEquals($packet, $tcpPacket);
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ParseException
     * @expectedExceptionMessage Packet crc (\x5190) does not match calculated crc (\x5191)!
     */
    public function testRtuPackWithInvalidCrc()
    {
        RtuConverter::fromRtu("\x00\x81\x03\x51\x90");
    }

    public function testRtuPackWithInvalidCrcIsRead()
    {
        /** @var ReadHoldingRegistersRequest $packet */
        $packet = RtuConverter::fromRtu("\x03\x03\x02\xCD\x6B\x00\x00", ['no_crc_check' => true]); // last 2 bytes for crc should be \xD4\xFB to be correct

        $this->assertInstanceOf(ReadHoldingRegistersResponse::class, $packet);

        $tcpPacket = new ReadHoldingRegistersResponse("\x02\xCD\x6B", 3, $packet->getHeader()->getTransactionId());
        $this->assertEquals($packet, $tcpPacket);
    }

}