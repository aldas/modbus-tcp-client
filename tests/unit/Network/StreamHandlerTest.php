<?php

namespace ModbusTcpClient\Network;

class MockData
{
    // $loopForever makes mock return always same data from stream/fread. microtime returns still current time
    public static $loopForever = false;

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

    if (MockData::$loopForever === true) {
        return MockData::$fread[0];
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

    if (MockData::$loopForever === true) {
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

    if (MockData::$loopForever === true) {
        return MockData::$stream_select[0];
    }

    return MockData::$stream_select[MockData::$stream_selectCounter++];
}


namespace Tests\unit\Network;

use ModbusTcpClient\Network\IOException;
use ModbusTcpClient\Network\MockData;
use ModbusTcpClient\Network\StreamHandler;
use ModbusTcpClient\Utils\Packet;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class StreamHandlerTest extends TestCase
{
    protected function tearDown(): void
    {
        MockData::$loopForever = false;
        MockData::$freadCounter = 0;
        MockData::$microtimeCounter = 0;
        MockData::$stream_selectCounter = 0;

        MockData::$fread = null;
        MockData::$microtime = null;
        MockData::$stream_select = null;
    }

    public function testReturnData()
    {
        // full packet is received with 2 fread calls (tcp packet fragmentation example)
        $returnedPacketPart1 = "\xda\x87\x00\x00\x00\x03\x00\x81";
        $returnedPacketPart2 = "\x03";
        $expected = $returnedPacketPart1 . $returnedPacketPart2;

        MockData::$fread = [$returnedPacketPart1, $returnedPacketPart2];
        MockData::$microtime = [1, 1, 1, 100];
        MockData::$stream_select = ['x', 'x'];

        $handler = new ForTestStreamHandler();

        $readStreams = ['stream'];
        $timeout = null;
        $logger = new MockLogger();

        $result = $handler->receiveFrom($readStreams, $timeout, $logger);

        $this->assertEquals([$expected], $result);
        $this->assertEquals(2, MockData::$freadCounter);

    }

    public function testSelectReturnButFreadFailure()
    {
        $this->expectExceptionMessage("fread error during receiveFrom");
        $this->expectException(IOException::class);

        // full packet is received with 2 fread calls (tcp packet fragmentation example)
        $returnedPacketPart1 = "\xda\x87\x00\x00\x00\x03\x00\x81";
        $returnedPacketPart2 = "\x03";
        $expected = $returnedPacketPart1 . $returnedPacketPart2;

        MockData::$loopForever = true;
        MockData::$fread = [false]; // fread() returns false - something is wrong with stream/socket
        MockData::$stream_select = ['x'];

        $handler = new ForTestStreamHandler();

        $readStreams = ['stream'];
        $timeout = null;
        $logger = new MockLogger();

        $result = $handler->receiveFrom($readStreams, $timeout, $logger);

        $this->assertEquals([$expected], $result);
        $this->assertEquals(2, MockData::$freadCounter);

    }

    public function testExceptionFromStreamSelect()
    {
        $this->expectExceptionMessage("stream_select interrupted by an incoming signal");
        $this->expectException(IOException::class);

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

    public function testExceptionFromReadTimeout()
    {
        $this->expectExceptionMessage("Read total timeout expired");
        $this->expectException(IOException::class);

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
        $packet = "\xda\x87\x00\x00\x00\x03\x00\x81\x03";
        MockData::$fread = [null, $packet]; // second read returns all packet bytes
        MockData::$microtime = [0, 0.001, 0.0002, 0.0003];
        MockData::$stream_select = ['x', 'x'];

        $handler = new ForTestStreamHandler();

        $readStreams = ['stream'];
        $timeout = null;
        $logger = new MockLogger();

        $result = $handler->receiveFrom($readStreams, $timeout, $logger);

        $this->assertEquals([$packet], $result);
    }
}

class ForTestStreamHandler
{
    use StreamHandler {
        receiveFrom as public;
    }

    protected function getIsCompleteCallback(): callable
    {
        return static function ($binaryData, $streamIndex) {
            return Packet::isCompleteLength($binaryData);
        };
    }
}

class MockLogger implements LoggerInterface
{
    public static array $data = [];

    public function debug($message, array $context = array())
    {
        static::$data[] = $message;
    }

    public function emergency($message, array $context = array())
    {
        static::$data[] = $message;
    }

    public function alert($message, array $context = array())
    {
        static::$data[] = $message;
    }

    public function critical($message, array $context = array())
    {
        static::$data[] = $message;
    }

    public function error($message, array $context = array())
    {
        static::$data[] = $message;
    }

    public function warning($message, array $context = array())
    {
        static::$data[] = $message;
    }

    public function notice($message, array $context = array())
    {
        static::$data[] = $message;
    }

    public function info($message, array $context = array())
    {
        static::$data[] = $message;
    }

    public function log($level, $message, array $context = array())
    {
        static::$data[] = $message;
    }
}
