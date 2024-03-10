<?php

namespace Tests\Packet;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Exception\ParseException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusApplicationHeader;
use ModbusTcpClient\Packet\ProtocolDataUnit;
use ModbusTcpClient\Utils\Types;
use PHPUnit\Framework\TestCase;

class ProtocolDataUnitTest extends TestCase
{
    public function testValidation()
    {
        $instance = new ProtocolDataUnitImpl(0, 255);

        $this->assertEquals(0, $instance->getHeader()->getUnitId());
        $this->assertEquals(255, $instance->getHeader()->getTransactionId());
    }

    public function testFailWithNegativeUnitID()
    {
        $this->expectExceptionMessage("unitId is out of range (0-255): -1");
        $this->expectException(InvalidArgumentException::class);

        new ProtocolDataUnitImpl(-1);
    }

    public function parseProvider(): array
    {
        return [
            'ok' => ["\x01\x38\x00\x00\x00\x02\x11\x0b", (new ProtocolDataUnitImpl(0x11, 0x0138))->__toString()],
            'null length' => ["", (new ErrorResponse(new ModbusApplicationHeader(2, 0, 1), 0x0b, 4))->__toString()],
            'invalid function code' => ["\x01\x38\x00\x00\x00\x02\x11\x01", (new ErrorResponse(new ModbusApplicationHeader(2, 0x11, 0x0138), 0x0b, 1))->__toString()],
            'invalid pdu len' => ["\x01\x38\x00\x00\x00\x03\x11\x0b", (new ErrorResponse(new ModbusApplicationHeader(2, 0x11, 0x0138), 0x0b, 3))->__toString()],
            'exception is caught' => ["\x01\x38\x00\x00\x00\x02\xAA\x0b", (new ErrorResponse(new ModbusApplicationHeader(2, 0xAA, 0x0138), 0x0b, 3))->__toString()],
        ];
    }

    /**
     * @dataProvider parseProvider
     */
    public function testParse($binaryData, $expect)
    {
        $packet = ProtocolDataUnitImpl::parse($binaryData);
        $is = $packet->__toString();
        if ($packet->getHeader()->getTransactionId() != 0x0138) {
            // transaction id is random for errors
            $is[0] = "\x00";
            $is[1] = "\x01";
        }
        $this->assertEquals($expect, $is);
    }
}

class ProtocolDataUnitImpl extends ProtocolDataUnit
{
    public function __construct($unitId = 0, $transactionId = null)
    {
        parent::__construct($unitId, $transactionId);
    }

    public function getFunctionCode(): int
    {
        return 11; // 0x0b
    }

    public function __toString(): string
    {
        return b''
            . $this->getHeader()->__toString()
            . Types::toByte($this->getFunctionCode());
    }

    protected function getLengthInternal(): int
    {
        return 1; // size of function code (1 byte)
    }

    public static function parse(string $binaryString): ProtocolDataUnitImpl|ErrorResponse
    {
        $packet = self::parsePacket(
            $binaryString,
            2,
            0x0b,
            function (int $transactionId, int $unitId) use ($binaryString) {
                if ($unitId === 0xAA) {
                    throw new ParseException('test error');
                }
                return new self($unitId, $transactionId);
            }
        );

        return $packet;
    }
}
