<?php

namespace Tests\integration;

use ModbusTcpClient\Composer\Read\ReadRegistersBuilder;
use ModbusTcpClient\Composer\Write\WriteRegistersBuilder;
use ModbusTcpClient\Network\NonBlockingClient;
use ModbusTcpClient\Network\ResultContainer;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleRegistersResponse;

class NonBlockingClientTest extends MockServerTestCase
{
    public function testSendReadRequests()
    {
        $mockResponse = '8180000000050003020003'; // respond with 1 WORD (2 bytes) [0, 3]

        list($response, $clientSentData) = static::executeWithNonBlockingClient($mockResponse, function ($uri) {
            return ReadRegistersBuilder::newReadHoldingRegisters($uri)
                ->int16(256, 'temperature', function ($value) {
                    return $value / 10;
                })
                ->build();
        });

        // did we sent correct packet?
        $packetWithoutTransactionId = substr($clientSentData[0], 4);
        $this->assertEquals('00000006000301000001', $packetWithoutTransactionId);

        // is response as we expect?
        $this->assertCount(1, $response);

        // was data extracted?
        $this->assertEquals(0.3, $response['temperature']);
    }

    public function testSendReadRequestsNoflatRequestResponse()
    {
        $mockResponse = '8180000000050003020003'; // respond with 1 WORD (2 bytes) [0, 3]

        list($responses, $clientSentData) = static::executeWithNonBlockingClient($mockResponse, function ($uri) {
            return ReadRegistersBuilder::newReadHoldingRegisters($uri)
                ->int16(256, 'temperature', function ($value) {
                    return $value / 10;
                })
                ->build();
        }, [NonBlockingClient::OPT_FLAT_REQUEST_RESPONSE => false]);

        // did we sent correct packet?
        $packetWithoutTransactionId = substr($clientSentData[0], 4);
        $this->assertEquals('00000006000301000001', $packetWithoutTransactionId);

        // is response as we expect?
        $this->assertCount(1, $responses);

        // was data extracted?
        $this->assertEquals(0.3, $responses[0]['temperature']);
        $this->assertEquals(0.3, $responses->getData()[0]['temperature']);
    }

    public function testSendWriteRequests()
    {
        $mockResponse = '013800000006111004100003'; // respond with 1 WORD (2 bytes) [0, 3]

        list($responses, $clientSentData) = static::executeWithNonBlockingClient($mockResponse, function ($uri) {
            return WriteRegistersBuilder::newWriteMultipleRegisters($uri)
                ->int16(256, 1)
                ->build();
        }, [NonBlockingClient::OPT_FLAT_REQUEST_RESPONSE => false]);

        // did we sent correct packet?
        $packetWithoutTransactionId = substr($clientSentData[0], 4);
        $this->assertEquals('00000009001001000001020001', $packetWithoutTransactionId);

        // is response as we expect?
        $this->assertCount(1, $responses);

        // was data extracted?
        $extracted = $responses[0];
        $this->assertInstanceOf(WriteMultipleRegistersResponse::class, $extracted);
    }

    public function testSendSingleRequest()
    {
        $mockResponse = '8180000000050003020003'; // respond with 1 WORD (2 bytes) [0, 3]

        $response = null;
        $clientSentData = static::executeWithMockServer($mockResponse, function ($port) use (&$response) {
            $host = getenv('MOCKSERVER_BIND_ADDRESS') ?: '127.0.0.1';
            $uri = "tcp://{$host}:{$port}";

            $request = WriteRegistersBuilder::newWriteMultipleRegisters($uri)
                ->int16(256, 1)
                ->build()[0];

            $client = new NonBlockingClient(['readTimeoutSec' => 0.1, NonBlockingClient::OPT_FLAT_REQUEST_RESPONSE => false]);
            $response = $client->sendRequest($request);
        });

        // did we sent correct packet?
        $packetWithoutTransactionId = substr($clientSentData[0], 4);
        $this->assertEquals('00000009001001000001020001', $packetWithoutTransactionId);

        $this->assertEquals([0, 3], $response[0]->getData());
    }

    public function testSendSingleRequestWithflatRequestResponse()
    {
        $mockResponse = '8180000000050003020003'; // respond with 1 WORD (2 bytes) [0, 3]

        $response = null;
        $clientSentData = static::executeWithMockServer($mockResponse, function ($port) use (&$response) {
            $host = getenv('MOCKSERVER_BIND_ADDRESS') ?: '127.0.0.1';
            $uri = "tcp://{$host}:{$port}";

            $request = ReadRegistersBuilder::newReadHoldingRegisters($uri)
                ->int16(256, 'temperature', function ($value) {
                    return $value / 10;
                })
                ->build()[0];

            $client = new NonBlockingClient(['readTimeoutSec' => 0.1, NonBlockingClient::OPT_FLAT_REQUEST_RESPONSE => true]);
            $response = $client->sendRequest($request);
        });

        // did we sent correct packet?
        $packetWithoutTransactionId = substr($clientSentData[0], 4);
        $this->assertEquals('00000006000301000001', $packetWithoutTransactionId);

        $this->assertEquals(['temperature' => 0.3], $response->getData());
    }

    public function testSendSingleRequestWithErrorResponse()
    {
        $mockResponse = 'da8700000003008103'; // respond with error response

        /** @var ResultContainer $response */
        $response = null;
        static::executeWithMockServer($mockResponse, function ($port) use (&$response) {
            $host = getenv('MOCKSERVER_BIND_ADDRESS') ?: '127.0.0.1';
            $uri = "tcp://{$host}:{$port}";

            $request = ReadRegistersBuilder::newReadHoldingRegisters($uri)
                ->int16(256, 'temperature', function ($value) {
                    return $value / 10;
                })
                ->build()[0];

            $client = new NonBlockingClient(['readTimeoutSec' => 0.1, NonBlockingClient::OPT_FLAT_REQUEST_RESPONSE => true]);
            $response = $client->sendRequest($request);
        });

        $this->assertTrue($response->hasErrors());
        $this->assertEquals('Illegal data value', $response->getErrors()[0]->getErrorMessage());
    }


    /**
     * @expectedException \ModbusTcpClient\Exception\ModbusException
     * @expectedExceptionMessage sendRequests resulted with modbus error. msg: Illegal data value
     */
    public function testSendSingleRequestShouldThrowException()
    {
        $mockResponse = 'da8700000003008103'; // respond with error response

        $response = null;
        static::executeWithMockServer($mockResponse, function ($port) use (&$response) {
            $host = getenv('MOCKSERVER_BIND_ADDRESS') ?: '127.0.0.1';
            $uri = "tcp://{$host}:{$port}";

            $request = WriteRegistersBuilder::newWriteMultipleRegisters($uri)
                ->int16(256, 1)
                ->build()[0];

            $client = new NonBlockingClient(['readTimeoutSec' => 0.1, NonBlockingClient::OPT_THROW_ON_ERROR => true]);
            $response = $client->sendRequest($request);
        });
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ModbusException
     * @expectedExceptionMessage sendPackets resulted with modbus error. msg: Illegal data value
     */
    public function testSendSinglePacketShouldThrowException()
    {
        $mockResponse = 'da8700000003008103'; // respond with error response

        $response = null;
        static::executeWithMockServer($mockResponse, function ($port) use (&$response) {
            $host = getenv('MOCKSERVER_BIND_ADDRESS') ?: '127.0.0.1';
            $uri = "tcp://{$host}:{$port}";

            $client = new NonBlockingClient(['readTimeoutSec' => 0.1, NonBlockingClient::OPT_THROW_ON_ERROR => true]);
            $response = $client->sendPacket(new ReadHoldingRegistersRequest(256, 1), $uri);
        });
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ModbusException
     * @expectedExceptionMessage sendPackets resulted with modbus error. msg: Illegal data value
     */
    public function testSendSinglePacketShouldThrowException2()
    {
        $mockResponse = 'da8700000003008103'; // respond with error response

        $response = null;
        static::executeWithMockServer($mockResponse, function ($port) use (&$response) {
            $host = getenv('MOCKSERVER_BIND_ADDRESS') ?: '127.0.0.1';
            $uri = "tcp://{$host}:{$port}";

            $client = new NonBlockingClient(['readTimeoutSec' => 0.1, NonBlockingClient::OPT_THROW_ON_ERROR => false]);
            $response = $client->sendPacket(new ReadHoldingRegistersRequest(256, 1), $uri, [NonBlockingClient::OPT_THROW_ON_ERROR => true]);
        });
    }

    public function testSendPacket()
    {
        $mockResponse = '8180000000050003020003'; // respond with 1 WORD (2 bytes) [0, 3]

        $response = null;
        $clientSentData = static::executeWithMockServer($mockResponse, function ($port) use (&$response) {
            $host = getenv('MOCKSERVER_BIND_ADDRESS') ?: '127.0.0.1';
            $uri = "tcp://{$host}:{$port}";

            $client = new NonBlockingClient(['readTimeoutSec' => 0.1]);
            $response = $client->sendPacket(new ReadHoldingRegistersRequest(256, 1), $uri);
        });

        // did we sent correct packet?
        $packetWithoutTransactionId = substr($clientSentData[0], 4);
        $this->assertEquals('00000006000301000001', $packetWithoutTransactionId);

        $this->assertEquals([0, 3], $response->getData());
    }

    public function testSendAllPackets()
    {
        $mockResponse = '8180000000050003020003'; // respond with 1 WORD (2 bytes) [0, 3]

        $responses = null;
        $clientSentData = static::executeWithMockServer($mockResponse, function ($port) use (&$responses) {
            $host = getenv('MOCKSERVER_BIND_ADDRESS') ?: '127.0.0.1';
            $uri = "tcp://{$host}:{$port}";

            $client = new NonBlockingClient(['readTimeoutSec' => 0.1]);
            $responses = $client->sendPackets([new ReadHoldingRegistersRequest(256, 1)], $uri);
        });

        // did we sent correct packet?
        $packetWithoutTransactionId = substr($clientSentData[0], 4);
        $this->assertEquals('00000006000301000001', $packetWithoutTransactionId);

        // is response as we expect?
        $this->assertCount(1, $responses);
        $response = $responses[0];
        $this->assertEquals([0, 3], $response->getData());
    }

}