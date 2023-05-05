<?php

namespace Tests\unit\Composer\Write\Register;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Composer\Write\Register\WriteRegisterAddress;
use ModbusTcpClient\Composer\Write\Register\WriteRegisterRequest;
use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleRegistersRequest;
use PHPUnit\Framework\TestCase;

class WriteRegisterRequestTest extends TestCase
{
    public function testCreate()
    {
        $uri = 'tcp://192.168.100.1:502';
        $addresses = [new WriteRegisterAddress(0, Address::TYPE_INT16, 1)];
        $request = new WriteMultipleRegistersRequest(1, [$addresses[0]->toBinary()]);

        $writeRequest = new WriteRegisterRequest($uri, $addresses, $request);

        $this->assertEquals($uri, $writeRequest->getUri());
        $this->assertEquals($request, $writeRequest->getRequest());
        $this->assertEquals($addresses, $writeRequest->getAddresses());
    }

    public function testToString()
    {
        $uri = 'tcp://192.168.100.1:502';
        $addresses = [new WriteRegisterAddress(0, Address::TYPE_INT16, 1)];
        $request = new WriteMultipleRegistersRequest(1, [$addresses[0]->toBinary()]);

        $writeRequest = new WriteRegisterRequest($uri, $addresses, $request);

        $this->assertEquals($request->__toString(), $writeRequest->__toString());
    }

    public function testParse()
    {
        $uri = 'tcp://192.168.100.1:502';
        $addresses = [
            new WriteRegisterAddress(0, Address::TYPE_INT16, 1)
        ];
        $request = new WriteMultipleRegistersRequest(1, [$addresses[0]->toBinary()]);

        $writeRequest = new WriteRegisterRequest($uri, $addresses, $request);

        $value = $writeRequest->parse("\x01\x38\x00\x00\x00\x06\x11\x10\x04\x10\x00\x03");
        $this->assertEquals([], $value);
    }

    public function testParseAnError()
    {
        $uri = 'tcp://192.168.100.1:502';
        $addresses = [
            new WriteRegisterAddress(0, Address::TYPE_INT16, 1)
        ];
        $request = new WriteMultipleRegistersRequest(1, [$addresses[0]->toBinary()]);

        $writeRequest = new WriteRegisterRequest($uri, $addresses, $request);

        $value = $writeRequest->parse("\xda\x87\x00\x00\x00\x03\x00\x81\x03");
        $this->assertInstanceOf(ErrorResponse::class, $value);
    }

    public function testParseNonResponse()
    {
        $this->expectExceptionMessage("given data is not valid modbus response");
        $this->expectException(InvalidArgumentException::class);

        $uri = 'tcp://192.168.100.1:502';
        $addresses = [
            new WriteRegisterAddress(0, Address::TYPE_INT16, 1)
        ];
        $request = new WriteMultipleRegistersRequest(1, [$addresses[0]->toBinary()]);

        $writeRequest = new WriteRegisterRequest($uri, $addresses, $request);

        $writeRequest->parse("\x81\x80\x00\x00\x00\x05\x03\x01\x02\xCD\x6B");
    }

}
