<?php

namespace Tests\integration;

use ModbusTcpClient\Composer\Read\ReadRegistersBuilder;
use ModbusTcpClient\Composer\Write\WriteCoilsBuilder;
use ModbusTcpClient\Composer\Write\WriteRegistersBuilder;
use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Network\NonBlockingClient;
use ModbusTcpClient\Network\ResultContainer;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;

class NonBlockingClientTest extends MockServerTestCase
{
    #[RunInSeparateProcess]
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

    #[RunInSeparateProcess]
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

    #[RunInSeparateProcess]
    public function testSendWriteCoilsRequests()
    {
        $mockResponse = '013800000006110F04100003';

        list($responses, $clientSentData) = static::executeWithNonBlockingClient($mockResponse, function ($uri) {
            return WriteCoilsBuilder::newWriteMultipleCoils($uri)
                ->coil(278, true)
                ->build();
        }, [NonBlockingClient::OPT_FLAT_REQUEST_RESPONSE => false]);

        // did we sent correct packet?
        $packetWithoutTransactionId = substr($clientSentData[0], 4);
        $this->assertEquals('00000008000f011600010101', $packetWithoutTransactionId);

        // is response as we expect?
        $this->assertCount(1, $responses); // is array of single empty array

        // was data extracted?
        $this->assertEquals([0 => []], $responses->getData()); // not flatted
    }

    #[RunInSeparateProcess]
    public function testSendWriteCoilsRequestsFlatted()
    {
        $mockResponse = '013800000006110F04100003';

        list($responses, $clientSentData) = static::executeWithNonBlockingClient($mockResponse, function ($uri) {
            return WriteCoilsBuilder::newWriteMultipleCoils($uri)
                ->coil(278, true)
                ->build();
        }, [NonBlockingClient::OPT_FLAT_REQUEST_RESPONSE => true]);

        // did we sent correct packet?
        $packetWithoutTransactionId = substr($clientSentData[0], 4);
        $this->assertEquals('00000008000f011600010101', $packetWithoutTransactionId);

        // is response as we expect?
        $this->assertCount(0, $responses); // is empty array

        // was data extracted?
        $this->assertEquals([], $responses->getData()); // flatted
    }

    #[RunInSeparateProcess]
    public function testSendSingleComposerWriteRequest()
    {
        $mockResponse = '013800000006111004100003';

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

        $this->assertEquals([0 => []], $response->getData()); // not flatted
    }

    #[RunInSeparateProcess]
    public function testSendSingleComposerWriteRequestFlatted()
    {
        $mockResponse = '013800000006111004100003';

        $response = null;
        $clientSentData = static::executeWithMockServer($mockResponse, function ($port) use (&$response) {
            $host = getenv('MOCKSERVER_BIND_ADDRESS') ?: '127.0.0.1';
            $uri = "tcp://{$host}:{$port}";

            $request = WriteRegistersBuilder::newWriteMultipleRegisters($uri)
                ->int16(256, 1)
                ->build()[0];

            $client = new NonBlockingClient(['readTimeoutSec' => 0.1, NonBlockingClient::OPT_FLAT_REQUEST_RESPONSE => true]);
            $response = $client->sendRequest($request);
        });

        // did we sent correct packet?
        $packetWithoutTransactionId = substr($clientSentData[0], 4);
        $this->assertEquals('00000009001001000001020001', $packetWithoutTransactionId);

        $this->assertEquals([], $response->getData()); // is flatted
    }

    #[RunInSeparateProcess]
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

    #[RunInSeparateProcess]
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

    #[RunInSeparateProcess]
    public function testSendSingleRequestShouldThrowException()
    {
        $this->expectExceptionMessage("sendRequests resulted with modbus error. msg: Illegal data value");
        $this->expectException(ModbusException::class);

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

    #[RunInSeparateProcess]
    public function testSendSinglePacketShouldThrowException()
    {
        $this->expectExceptionMessage("sendPackets resulted with modbus error. msg: Illegal data value");
        $this->expectException(ModbusException::class);

        $mockResponse = 'da8700000003008103'; // respond with error response

        $response = null;
        static::executeWithMockServer($mockResponse, function ($port) use (&$response) {
            $host = getenv('MOCKSERVER_BIND_ADDRESS') ?: '127.0.0.1';
            $uri = "tcp://{$host}:{$port}";

            $client = new NonBlockingClient(['readTimeoutSec' => 0.1, NonBlockingClient::OPT_THROW_ON_ERROR => true]);
            $response = $client->sendPacket(new ReadHoldingRegistersRequest(256, 1), $uri);
        });
    }

    #[RunInSeparateProcess]
    public function testSendSinglePacketShouldThrowException2()
    {
        $this->expectExceptionMessage("sendPackets resulted with modbus error. msg: Illegal data value");
        $this->expectException(ModbusException::class);

        $mockResponse = 'da8700000003008103'; // respond with error response

        $response = null;
        static::executeWithMockServer($mockResponse, function ($port) use (&$response) {
            $host = getenv('MOCKSERVER_BIND_ADDRESS') ?: '127.0.0.1';
            $uri = "tcp://{$host}:{$port}";

            $client = new NonBlockingClient(['readTimeoutSec' => 0.1, NonBlockingClient::OPT_THROW_ON_ERROR => false]);
            $response = $client->sendPacket(new ReadHoldingRegistersRequest(256, 1), $uri, [NonBlockingClient::OPT_THROW_ON_ERROR => true]);
        });
    }

    #[RunInSeparateProcess]
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

    #[RunInSeparateProcess]
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
