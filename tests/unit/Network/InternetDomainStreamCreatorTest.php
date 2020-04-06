<?php

namespace ModbusTcpClient\Network;

class SSCMockData
{
    public static $stream_socket_client = null;
    public static $stream_socket_clientCounter = 0;

    public static $stream_context_create = null;
    public static $stream_context_createOpts = null;
    public static $stream_context_createCounter = 0;
}

function stream_socket_client($remote_socket, &$errno = null, &$errstr = null, $timeout = null, $flags = null, $context = null)
{
    if (SSCMockData::$stream_socket_client === null) {
        return \stream_socket_client($remote_socket, $errno, $errstr, $timeout, $flags, $context);
    }

    return SSCMockData::$stream_socket_client[SSCMockData::$stream_socket_clientCounter++];
}

function stream_context_create(array $options = null, array $params = null)
{
    if (SSCMockData::$stream_context_create === null) {
        return \stream_context_create($options, $params);
    }

    SSCMockData::$stream_context_createOpts[] = $options;
    return SSCMockData::$stream_context_create[SSCMockData::$stream_context_createCounter++];
}

namespace Tests\unit\Network;


use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Network\InternetDomainStreamCreator;
use ModbusTcpClient\Network\SSCMockData;
use ModbusTcpClient\Network\StreamCreator;
use PHPUnit\Framework\TestCase;

class InternetDomainStreamCreatorTest extends TestCase
{
    protected function tearDown()
    {
        SSCMockData::$stream_socket_client = null;
        SSCMockData::$stream_socket_clientCounter = 0;

        SSCMockData::$stream_context_create = null;
        SSCMockData::$stream_context_createOpts = [];
        SSCMockData::$stream_context_createCounter = 0;
    }

    public function testCreate()
    {
        SSCMockData::$stream_socket_client = ['stream'];

        $connection = BinaryStreamConnection::getBuilder()
            ->setProtocol(StreamCreator::TYPE_TCP)
            ->setUri('tcp://127.0.0.1:502')
            ->build();

        $stream = (new InternetDomainStreamCreator())->createStream($connection);

        $this->assertEquals('stream', $stream);
    }

    /**
     * @expectedException \ModbusTcpClient\Network\IOException
     * @expectedExceptionMessage Unable to create client socket to tcp://127.0.0.1:1: Connection refused
     */
    public function testExceptionCreatingStream()
    {
        $connection = BinaryStreamConnection::getBuilder()
            ->setProtocol(StreamCreator::TYPE_TCP)
            ->setUri('tcp://127.0.0.1:1')
            ->build();

        (new InternetDomainStreamCreator())->createStream($connection);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage Unknown protocol, should be 'TCP' or 'UDP'
     */
    public function testUnknownProtocol()
    {
        $connection = BinaryStreamConnection::getBuilder()
            ->setProtocol(StreamCreator::TYPE_SERIAL)
            ->setHost('localhost')
            ->build();

        (new InternetDomainStreamCreator())->createStream($connection);
    }

    public function testBindClient()
    {
        SSCMockData::$stream_socket_client = ['stream'];
        SSCMockData::$stream_context_create = ['context'];

        $connection = BinaryStreamConnection::getBuilder()
            ->setProtocol(StreamCreator::TYPE_TCP)
            ->setUri('tcp://127.0.0.1:502')
            ->setClient('localhost')
            ->setClientPort(1)
            ->build();

        $stream = (new InternetDomainStreamCreator())->createStream($connection);

        $this->assertEquals('stream', $stream);
        $this->assertEquals([['socket' => ['bindto' => 'localhost:1']]], SSCMockData::$stream_context_createOpts);
    }
}
