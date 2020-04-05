<?php

namespace Tests\unit\Composer\Write\Coil;


use ModbusTcpClient\Composer\Write\Coil\WriteCoilAddress;
use ModbusTcpClient\Composer\Write\Coil\WriteCoilRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleCoilsRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleCoilsResponse;
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
        $this->assertInstanceOf(WriteMultipleCoilsResponse::class, $value);
    }

}
