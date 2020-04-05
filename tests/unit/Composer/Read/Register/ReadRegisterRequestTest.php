<?php

namespace Tests\unit\Composer\Read\Register;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Composer\Read\Register\BitReadRegisterAddress;
use ModbusTcpClient\Composer\Read\Register\ReadRegisterAddress;
use ModbusTcpClient\Composer\Read\Register\ReadRegisterRequest;
use ModbusTcpClient\Composer\Read\Register\StringReadRegisterAddress;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use PHPUnit\Framework\TestCase;

class ReadRegisterRequestTest extends TestCase
{
    public function testCreate()
    {
        $uri = 'tcp://192.168.100.1:502';
        $addresses = [new BitReadRegisterAddress(1, 0, 'alarm1_do')];
        $request = new ReadHoldingRegistersRequest(1, 1);

        $rr = new ReadRegisterRequest($uri, $addresses, $request);

        $this->assertEquals($uri, $rr->getUri());
        $this->assertEquals($request, $rr->getRequest());
        $this->assertEquals($addresses, $rr->getAddresses());
    }

    public function testToString()
    {
        $uri = 'tcp://192.168.100.1:502';
        $addresses = [new BitReadRegisterAddress(1, 0, 'alarm1_do')];
        $request = new ReadHoldingRegistersRequest(1, 1);

        $rr = new ReadRegisterRequest($uri, $addresses, $request);

        $this->assertEquals($request->__toString(), $rr->__toString());
    }

    public function testParse()
    {
        $uri = 'tcp://192.168.100.1:502';
        $addresses = [
            new ReadRegisterAddress(0, Address::TYPE_INT16, 'temp1_wo'),
            new StringReadRegisterAddress(1, 5, 'username', function ($data) {
                return 'prefix_' . $data;
            })
        ];
        $request = new ReadHoldingRegistersRequest(0, 4);

        $rr = new ReadRegisterRequest($uri, $addresses, $request);

        $values = $rr->parse("\x81\x80\x00\x00\x00\x0B\x01\x03\x08\x01\x00\xF8\x53\x65\x72\x00\x6E");
        $this->assertEquals(
            ['username' => 'prefix_Søren', 'temp1_wo' => 256],
            $values
        );
    }
}
