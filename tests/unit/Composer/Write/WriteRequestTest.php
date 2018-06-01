<?php

namespace Tests\unit\Composer\Write;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Composer\Write\WriteAddress;
use ModbusTcpClient\Composer\Write\WriteRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleRegistersResponse;
use PHPUnit\Framework\TestCase;

class WriteRequestTest extends TestCase
{
    public function testCreate()
    {
        $uri = 'tcp://192.168.100.1:502';
        $addresses = [new WriteAddress(0, Address::TYPE_INT16, 1)];
        $request = new WriteMultipleRegistersRequest(1, [$addresses[0]->toBinary()]);

        $writeRequest = new WriteRequest($uri, $addresses, $request);

        $this->assertEquals($uri, $writeRequest->getUri());
        $this->assertEquals($request, $writeRequest->getRequest());
        $this->assertEquals($addresses, $writeRequest->getAddresses());
    }

    public function testToString()
    {
        $uri = 'tcp://192.168.100.1:502';
        $addresses = [new WriteAddress(0, Address::TYPE_INT16, 1)];
        $request = new WriteMultipleRegistersRequest(1, [$addresses[0]->toBinary()]);

        $writeRequest = new WriteRequest($uri, $addresses, $request);

        $this->assertEquals($request->__toString(), $writeRequest->__toString());
    }

    public function testParse()
    {
        $uri = 'tcp://192.168.100.1:502';
        $addresses = [
            new WriteAddress(0, Address::TYPE_INT16, 1)
        ];
        $request = new WriteMultipleRegistersRequest(1, [$addresses[0]->toBinary()]);

        $writeRequest = new WriteRequest($uri, $addresses, $request);

        $value = $writeRequest->parse("\x01\x38\x00\x00\x00\x06\x11\x10\x04\x10\x00\x03");
        $this->assertInstanceOf(WriteMultipleRegistersResponse::class, $value);
    }

}