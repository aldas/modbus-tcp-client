<?php

namespace Tests\unit\Packet;


use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusApplicationHeader;
use PHPUnit\Framework\TestCase;

class ErrorResponseTest extends TestCase
{
    /**
     * @dataProvider errorMessageProvider
     */
    public function testErrorMessages($errorCode, $expectedMessage)
    {
        $address = new ErrorResponse(new ModbusApplicationHeader(1), 1, $errorCode);
        $this->assertEquals($expectedMessage, $address->getErrorMessage());
    }

    public function errorMessageProvider(): array
    {
        return [
            'errorCode 1 means "Illegal function"' => [1, 'Illegal function'],
            'errorCode 2 means "Illegal data address"' => [2, 'Illegal data address'],
            'errorCode 3 means "Illegal data value"' => [3, 'Illegal data value'],
            'errorCode 4 means "Server failure"' => [4, 'Server failure'],
            'errorCode 5 means "Acknowledge"' => [5, 'Acknowledge'],
            'errorCode 6means "Server busy"' => [6, 'Server busy'],
            'errorCode 10 means "Gateway path unavailable"' => [10, 'Gateway path unavailable'],
            'errorCode 11 means "Gateway targeted device failed to respond"' => [11, 'Gateway targeted device failed to respond'],
            'errorCode 12 is unknown code' => [12, 'Uknown error code (12)'],
        ];
    }

    public function testGetHeader()
    {
        $header = new ModbusApplicationHeader(1);
        $address = new ErrorResponse($header, 1, 1);

        $this->assertEquals($header, $address->getHeader());
    }

    public function testToString()
    {
        $address = new ErrorResponse(new ModbusApplicationHeader(2, 0, 55943), 1, 3);

        $this->assertEquals("\xda\x87\x00\x00\x00\x03\x00\x81\x03", $address->__toString());
    }

    public function testGetLength()
    {
        $address = new ErrorResponse(new ModbusApplicationHeader(2), 1, 1);

        $this->assertEquals(2, $address->getLength());
    }

    public function testToHex()
    {
        $address = new ErrorResponse(new ModbusApplicationHeader(2, 1, 255), 1, 1);

        $this->assertEquals('00ff00000003018101', $address->toHex());
    }

    public function testWithStartAddress()
    {
        $address = new ErrorResponse(new ModbusApplicationHeader(2), 1, 1);
        $withStartAddress = $address->withStartAddress(1);

        $this->assertEquals($address->toHex(), $withStartAddress->toHex());
    }


}