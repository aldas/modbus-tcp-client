<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleRegisterRequest;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class WriteSingleRegisterRequestTest extends TestCase
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
            "\x00\x01\x00\x00\x00\x06\x11\x06\x00\x6B\x01\x01",
            (new WriteSingleRegisterRequest(107, 257, 17, 1))->__toString()
        );
    }

    public function validationFailureProvider(): array
    {
        return [
            'validation fails over max uint16"' => [65536, 'value is not set or out of range (u)int16: 65536'],
            'validation fails below min int16"' => [-32769, 'value is not set or out of range (u)int16: -32769'],
            'validation success at min int16"' => [-32768, ''],
            'validation success at max uint16"' => [65535, ''],
            'validation success at 65534 (between ok range)"' => [65534, ''],
        ];
    }

    /**
     * @dataProvider validationFailureProvider
     */
    public function testValueValidationException($value, $expectedMessage)
    {
        try {
            $r = new WriteSingleRegisterRequest(107, $value, 17, 1);
            $this->assertEquals($value, $r->getValue());
        } catch (\Exception $e) {
            $this->expectException(InvalidArgumentException::class);
            $this->expectExceptionMessage($expectedMessage);
            throw $e;
        }
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

    public function testParse()
    {
        $packet = WriteSingleRegisterRequest::parse("\x00\x01\x00\x00\x00\x06\x11\x06\x00\x6B\x01\x01");
        $this->assertEquals($packet, (new WriteSingleRegisterRequest(107, 257, 17, 1))->__toString());
        $this->assertEquals(107, $packet->getStartAddress());
        $this->assertEquals(257, $packet->getValue());
        $this->assertEquals("\x01\x01", $packet->getValueAsWord()->getData());

        $header = $packet->getHeader();
        $this->assertEquals(1, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(6, $header->getLength());
        $this->assertEquals(17, $header->getUnitId());
    }

    public function testParseShouldReturnErrorResponseForTooShortPacket()
    {
        $packet = WriteSingleRegisterRequest::parse("\x00\x01\x00\x00\x00\x06\x11\x06\x00\x6B\x01");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        // transaction id is random
        $toString[0] = "\x00";
        $toString[1] = "\x00";
        self::assertEquals("\x00\x00\x00\x00\x00\x03\x00\x86\x04", $toString);
    }

    public function testParseShouldReturnErrorResponseForInvalidFunction()
    {
        $packet = WriteSingleRegisterRequest::parse("\x00\x01\x00\x00\x00\x06\x11\x00\x00\x6B\x01\x01");
        self::assertInstanceOf(ErrorResponse::class, $packet);
        $toString = $packet->__toString();
        self::assertEquals("\x00\x01\x00\x00\x00\x03\x11\x86\x01", $toString);
    }
}
