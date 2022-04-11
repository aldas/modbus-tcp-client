<?php

namespace Test\integration;

use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ResponseFactory;
use Psr\Log\LoggerInterface;
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

        $check = [
            'Connected',
            'Data sent',
            'Polling data',
            // last one is changing due resource identifier
        ];
        $this->assertCount(4, $logger->data);
        foreach ($check as $key => $value) {
            $this->assertSame($value, $logger->data[$key]);
        }
    }
}

class MockLogger implements LoggerInterface
{
    public $data = [];

    public function debug($message, array $context = array())
    {
        $this->data[] = $message;
    }

    public function emergency($message, array $context = array())
    {
        // TODO: Implement emergency() method.
    }

    public function alert($message, array $context = array())
    {
        // TODO: Implement alert() method.
    }

    public function critical($message, array $context = array())
    {
        // TODO: Implement critical() method.
    }

    public function error($message, array $context = array())
    {
        // TODO: Implement error() method.
    }

    public function warning($message, array $context = array())
    {
        // TODO: Implement warning() method.
    }

    public function notice($message, array $context = array())
    {
        // TODO: Implement notice() method.
    }

    public function info($message, array $context = array())
    {
        // TODO: Implement info() method.
    }

    public function log($level, $message, array $context = array())
    {
        // TODO: Implement log() method.
    }
}
