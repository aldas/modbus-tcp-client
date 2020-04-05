<?php

namespace Tests\unit\Composer\Read\Coil;

use ModbusTcpClient\Composer\Read\Coil\ReadCoilAddress;
use ModbusTcpClient\Composer\Read\Coil\ReadCoilRequest;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsRequest;
use PHPUnit\Framework\TestCase;

class ReadCoilRequestTest extends TestCase
{

    public function testCreate()
    {
        $uri = 'tcp://192.168.100.1:502';
        $addresses = [new ReadCoilAddress(1, 'alarm1_do')];
        $request = new ReadCoilsRequest(1, 1);

        $rr = new ReadCoilRequest($uri, $addresses, $request);

        $this->assertEquals($uri, $rr->getUri());
        $this->assertEquals($request, $rr->getRequest());
        $this->assertEquals($addresses, $rr->getAddresses());
    }

    public function testToString()
    {
        $uri = 'tcp://192.168.100.1:502';
        $addresses = [new ReadCoilAddress(1, 'alarm1_do')];
        $request = new ReadCoilsRequest(1, 1);

        $rr = new ReadCoilRequest($uri, $addresses, $request);

        $this->assertEquals($request->__toString(), $rr->__toString());
    }

    public function testParse()
    {
        $payload = "\x81\x80\x00\x00\x00\x05\x03\x01\x02\x01\x00";

        $addresses = [new ReadCoilAddress(1, 'alarm1_do')];
        $request = new ReadCoilsRequest(1, 1);

        $rr = new ReadCoilRequest('tcp://192.168.100.1:502', $addresses, $request);

        $values = $rr->parse($payload);

        $this->assertEquals(['alarm1_do' => true], $values);
    }

    public function testParseErrorResponse()
    {
        $payload = "\xda\x87\x00\x00\x00\x03\x00\x81\x03";

        $addresses = [new ReadCoilAddress(1, 'alarm1_do')];
        $request = new ReadCoilsRequest(1, 1);

        $rr = new ReadCoilRequest('tcp://192.168.100.1:502', $addresses, $request);

        $values = $rr->parse($payload);

        $this->assertInstanceOf(ErrorResponse::class, $values);
    }
}
