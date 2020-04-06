<?php

namespace ModbusTcpClient\Network;

class MockData
{
    public static $fread = null;
    public static $freadCounter = 0;

    public static $microtime = null;
    public static $microtimeCounter = 0;

    public static $stream_select = null;
    public static $stream_selectCounter = 0;

    public static $stream_socket_client = null;
    public static $stream_socket_clientCounter = 0;
}

/**
 * Override fread() in current namespace for testing
 * @return false|string
 */
function fread($handle, $length)
{
    if (MockData::$fread === null) {
        return \fread($handle, $length);
    }

    return MockData::$fread[MockData::$freadCounter++];
}

/**
 * Override microtime() in current namespace for testing
 * @return float
 */
function microtime($get_as_float = null)
{
    if (MockData::$microtime === null) {
        return \microtime($get_as_float);
    }

    return MockData::$microtime[MockData::$microtimeCounter++];
}

/**
 * Override stream_select() in current namespace for testing
 * @return int
 */
function stream_select(array &$read, array &$write, array &$except, $tv_sec, $tv_usec = null)
{
    if (MockData::$stream_select === null) {
        return \stream_select($read, $write, $except, $tv_sec, $tv_usec);
    }

    return MockData::$stream_select[MockData::$stream_selectCounter++];
}


namespace Tests\unit\Network;

use ModbusTcpClient\Network\MockData;
use ModbusTcpClient\Network\StreamHandler;
use PHPUnit\Framework\TestCase;

class StreamHandlerTest extends TestCase
{
    protected function tearDown()
    {
        MockData::$freadCounter = 0;
        MockData::$microtimeCounter = 0;
        MockData::$stream_selectCounter = 0;

        MockData::$fread = null;
        MockData::$microtime = null;
        MockData::$stream_select = null;
    }

    public function testReturnData()
    {
        MockData::$fread = ['x'];
        MockData::$microtime = [1, 100];
        MockData::$stream_select = ['x'];

        $handler = new ForTestStreamHandler();

        $readStreams = ['stream'];
        $timeout = null;
        $logger = new MockLogger();

        $result = $handler->receiveFrom($readStreams, $timeout, $logger);

        $this->assertEquals(['x'], $result);

    }

    /**
     * @expectedException \ModbusTcpClient\Network\IOException
     * @expectedExceptionMessage stream_select interrupted by an incoming signal
     */
    public function testExceptionFromStreamSelect()
    {
        MockData::$fread = ['x'];
        MockData::$microtime = [1, 100];
        MockData::$stream_select = [false];

        $handler = new ForTestStreamHandler();

        $readStreams = ['stream'];
        $timeout = null;
        $logger = new MockLogger();

        $result = $handler->receiveFrom($readStreams, $timeout, $logger);

        $this->assertEquals(['x'], $result);

    }

    /**
     * @expectedException \ModbusTcpClient\Network\IOException
     * @expectedExceptionMessage Read total timeout expired
     */
    public function testExceptionFromReadTimeout()
    {
        MockData::$fread = [null];
        MockData::$microtime = [0, 100];
        MockData::$stream_select = ['x'];

        $handler = new ForTestStreamHandler();

        $readStreams = ['stream'];
        $timeout = null;
        $logger = new MockLogger();

        $result = $handler->receiveFrom($readStreams, $timeout, $logger);

        $this->assertEquals(['x'], $result);

    }

    public function testNoExceptionFromNoData()
    {
        MockData::$fread = [null, 'x'];
        MockData::$microtime = [0, 0.001, 0.0002, 0.0003];
        MockData::$stream_select = ['x', 'x'];

        $handler = new ForTestStreamHandler();

        $readStreams = ['stream'];
        $timeout = null;
        $logger = new MockLogger();

        $result = $handler->receiveFrom($readStreams, $timeout, $logger);

        $this->assertEquals(['x'], $result);
    }
}

class ForTestStreamHandler
{
    use StreamHandler {
        receiveFrom as public;
    }
}

class MockLogger
{
    public static $data = [];

    public function debug($message, array $context = array())
    {
        $data[] = $message;
    }
}
