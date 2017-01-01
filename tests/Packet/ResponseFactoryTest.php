<?php


namespace Tests\Packet;


use ModbusTcpClient\Packet\ExceptionResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Packet\ResponseFactory;
use PHPUnit\Framework\TestCase;

class ResponseFactoryTest extends TestCase
{
    public function testShouldParseExceptionResponse()
    {
        $data = "\xda\x87\x00\x00\x00\x03\x00\x81\x03";

        $response = ResponseFactory::parseResponse($data);

        $this->assertInstanceOf(ExceptionResponse::class, $response);
        $this->assertEquals(1, $response->getFunctionCode());
        $this->assertEquals(3, $response->getErrorCode());
    }

    public function testShouldParseReadHoldingRegistersResponse()
    {
        //81 80 00 00 00 05 01 03 02 00 03
        $data = "\x81\x80\x00\x00\x00\x05\x01\x03\x02\x00\x03";

        $response = ResponseFactory::parseResponse($data);

        $this->assertInstanceOf(ReadHoldingRegistersResponse::class, $response);
        $this->assertEquals(3, $response->getFunctionCode());
        $this->assertEquals(2, $response->getLength());
        $this->assertEquals("\x00\x03", $response->getRawData());
        $this->assertEquals([0, 3], $response->getData());

        $header = $response->getHeader();
        $this->assertEquals(1, $header->getUnitId());
        $this->assertEquals(0x8180, $header->getTransactionId());
    }

}