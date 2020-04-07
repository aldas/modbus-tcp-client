<?php

namespace Test\integration;

use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ResponseFactory;
use Tests\integration\MockServerTestCase;

class Fc3ReadHoldingRegistersTest extends MockServerTestCase
{
    public function testFc3Read1Word()
    {
        $logger = new MockLogger();
        $request = new ReadHoldingRegistersRequest(256, 1);

        $mockResponse = '8180000000050003020003'; // respond with 1 WORD (2 bytes) [0, 3]
        list($responseBinary, $clientSentData) = static::executeWithMock($mockResponse, $request, $logger);

        $response = ResponseFactory::parseResponseOrThrow($responseBinary);
        $this->assertEquals([0, 3], $response->getData());

        $packetWithoutTransactionId = substr($clientSentData[0], 4);
        $this->assertEquals('00000006000301000001', $packetWithoutTransactionId);
        $this->assertCount(4, $logger->data);
        $this->assertArraySubset([
            'Connected',
            'Data sent',
            'Polling data',
            // last one is changing due resource identifier
        ], $logger->data);
    }
}

class MockLogger
{
    public $data = [];

    public function debug($message, array $context = array())
    {
        $this->data[] = $message;
    }
}
