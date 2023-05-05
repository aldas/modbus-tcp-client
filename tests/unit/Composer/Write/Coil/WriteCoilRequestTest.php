<?php

namespace Tests\unit\Composer\Write\Coil;


use ModbusTcpClient\Composer\Write\Coil\WriteCoilAddress;
use ModbusTcpClient\Composer\Write\Coil\WriteCoilRequest;
use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleCoilsRequest;
use PHPUnit\Framework\TestCase;

class WriteCoilRequestTest extends TestCase
{
    public function testCreate()
    {
        $uri = 'tcp://192.168.100.1:502';
        $addresses = [new WriteCoilAddress(0, true)];
        $request = new WriteMultipleCoilsRequest(1, [$addresses[0]->getValue()]);

        $writeRequest = new WriteCoilRequest($uri, $addresses, $request);

        $this->assertEquals($uri, $writeRequest->getUri());
        $this->assertEquals($request, $writeRequest->getRequest());
        $this->assertEquals($addresses, $writeRequest->getAddresses());
    }

    public function testToString()
    {
        $uri = 'tcp://192.168.100.1:502';
        $addresses = [new WriteCoilAddress(0, true)];
        $request = new WriteMultipleCoilsRequest(1, [$addresses[0]->getValue()]);

        $writeRequest = new WriteCoilRequest($uri, $addresses, $request);

        $this->assertEquals($request->__toString(), $writeRequest->__toString());
    }

    public function testParse()
    {
        $request = new WriteMultipleCoilsRequest(1, [true]);

        $writeRequest = new WriteCoilRequest('tcp://192.168.100.1:502', [], $request);

        $value = $writeRequest->parse("\x01\x38\x00\x00\x00\x06\x11\x0F\x04\x10\x00\x03");
        $this->assertEquals([], $value);
    }

    public function testParseAnError()
    {
        $request = new WriteMultipleCoilsRequest(1, [true]);

        $writeRequest = new WriteCoilRequest('tcp://192.168.100.1:502', [], $request);

        $value = $writeRequest->parse("\xda\x87\x00\x00\x00\x03\x00\x81\x03");
        $this->assertInstanceOf(ErrorResponse::class, $value);
    }

    public function testParseNonResponse()
    {
        $this->expectExceptionMessage("given data is not valid modbus response");
        $this->expectException(InvalidArgumentException::class);

        $request = new WriteMultipleCoilsRequest(1, [true]);

        $writeRequest = new WriteCoilRequest('tcp://192.168.100.1:502', [], $request);

        $writeRequest->parse("\x81\x80\x00\x00\x00\x05\x03\x01\x02\xCD\x6B");
    }

}
