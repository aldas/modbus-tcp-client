<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Exception\ParseException;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class ReadCoilsResponseTest extends TestCase
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
            "\x81\x80\x00\x00\x00\x05\x03\x01\x02\xCD\x6B",
            (new ReadCoilsResponse("\x02\xCD\x6B", 3, 33152))->__toString()
        );
    }

    public function testToHex()
    {
        $this->assertEquals(
            '818000000005030102cd6b',
            (new ReadCoilsResponse("\x02\xCD\x6B", 3, 33152))->toHex()
        );
    }

    public function testPacketProperties()
    {
        $packet = new ReadCoilsResponse("\x02\xCD\x6B", 3, 33152);
        $this->assertEquals(ModbusPacket::READ_COILS, $packet->getFunctionCode());

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

    public function testIterator()
    {
        $packet = (new ReadCoilsResponse("\x02\xCD\x6B", 3, 33152))->withStartAddress(50);

        $result = [];
        foreach ($packet as $address => $coil) {
            $result[$address] = $coil;
        }

        $this->assertEquals(
            [
                // hex: CD -> bin: 1100 1101 -> reverse for user input: 1011 0011
                50 => true,
                51 => false,
                52 => true,
                53 => true,
                54 => false,
                55 => false,
                56 => true,
                57 => true,

                // hex: 6B -> bin: 0110 1011 -> reverse for user input: 1101 0110
                58 => true,
                59 => true,
                60 => false,
                61 => true,
                62 => false,
                63 => true,
                64 => true,
                65 => false,

            ],
            $result
        );
    }

    public function testOffsetSet()
    {
        $this->expectExceptionMessage("setting value in response is not supported!");
        $this->expectException(ModbusException::class);

        $packet = (new ReadCoilsResponse("\x02\xCD\x6B", 3, 33152))->withStartAddress(50);
        $packet[50] = 1;
    }

    public function testOffsetUnSet()
    {
        $this->expectExceptionMessage("unsetting value in response is not supported!");
        $this->expectException(ModbusException::class);

        $packet = (new ReadCoilsResponse("\x02\xCD\x6B", 3, 33152))->withStartAddress(50);
        unset($packet[50]);
    }

    public function testOffsetExists()
    {
        $packet = (new ReadCoilsResponse("\x02\xCD\x6B", 3, 33152))->withStartAddress(50);

        $this->assertFalse(isset($packet[49]));
        $this->assertTrue(isset($packet[50]));
        $this->assertTrue(isset($packet[65]));
        $this->assertFalse(isset($packet[66]));
    }

    public function testOffsetGet()
    {
        $packet = (new ReadCoilsResponse("\x02\xCD\x6B", 3, 33152))->withStartAddress(50);

        $this->assertTrue($packet[50]);
        $this->assertFalse($packet[65]);
    }

    public function testOffsetGetOutOfBoundsUnder()
    {
        $this->expectExceptionMessage("offset out of bounds");
        $this->expectException(InvalidArgumentException::class);

        $packet = (new ReadCoilsResponse("\x02\xCD\x6B", 3, 33152))->withStartAddress(50);

        $packet[49];
    }

    public function testOffsetGetOutOfBoundsOver()
    {
        $this->expectExceptionMessage("offset out of bounds");
        $this->expectException(InvalidArgumentException::class);

        $packet = (new ReadCoilsResponse("\x02\xCD\x6B", 3, 33152))->withStartAddress(50);

        $packet[66];
    }

    public function testFailWhenByteCountDoesNotMatch()
    {
        $this->expectExceptionMessage("packet byte count does not match bytes in packet! count: 3, actual: 2");
        $this->expectException(ParseException::class);

        new ReadCoilsResponse("\x03\xCD\x6B", 3, 33152);
    }

}
