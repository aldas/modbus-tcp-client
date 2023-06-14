<?php

namespace Tests\unit\Network;


use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Network\StreamCreator;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class BinaryStreamConnectionTest extends TestCase
{
    public function testSetOptions()
    {
        $connection = BinaryStreamConnection::getBuilder()->setFromOptions([
            'host' => 'localhost',
            'port' => 5022
        ])->build();

        $this->assertEquals('localhost', $connection->getHost());
        $this->assertEquals(5022, $connection->getPort());
    }

    public function testSet()
    {
        $logger = new SetLogger();

        $connection = BinaryStreamConnection::getBuilder()
            ->setClient('127.0.0.2')
            ->setClientPort(32000)
            ->setTimeoutSec(1.2)
            ->setConnectTimeoutSec(1.3)
            ->setReadTimeoutSec(1.4)
            ->setWriteTimeoutSec(1.5)
            ->setDelayRead(100)
            ->setProtocol('udp')
            ->setHost('localhost')
            ->setPort(5022)
            ->setUri('tcp://192.168.100.1:502')
            ->setCreateStreamCallback(function () {
                return null;
            })
            ->setIsCompleteCallback(function () {
                return true;
            })
            ->setLogger($logger)
            ->build();

        $this->assertEquals('127.0.0.2', $connection->getClient());
        $this->assertEquals(32000, $connection->getClientPort());
        $this->assertEquals(1.2, $connection->getTimeoutSec());
        $this->assertEquals(1.3, $connection->getConnectTimeoutSec());
        $this->assertEquals(1.4, $connection->getReadTimeoutSec());
        $this->assertEquals(1.5, $connection->getWriteTimeoutSec());
        $this->assertEquals(100, $connection->getDelayRead());
        $this->assertEquals('udp', $connection->getProtocol());
        $this->assertEquals('localhost', $connection->getHost());
        $this->assertEquals(5022, $connection->getPort());
        $this->assertEquals('tcp://192.168.100.1:502', $connection->getUri());
        $this->assertEquals($logger, $connection->getLogger());
    }

    public function testCanNotBuildWithoutHostOrUri()
    {
        $this->expectExceptionMessage("host or uri property can not be left null or empty!");
        $this->expectException(InvalidArgumentException::class);

        BinaryStreamConnection::getBuilder()->setPort(5022)->build();
    }

    public function testSetSerial()
    {
        $connection = BinaryStreamConnection::getBuilder()
            ->setUri('/dev/ttyUSB0')
            ->setProtocol(StreamCreator::TYPE_SERIAL)
            ->build();

        $this->assertEquals('/dev/ttyUSB0', $connection->getUri());
        $this->assertEquals(StreamCreator::TYPE_SERIAL, $connection->getProtocol());
    }

}

class SetLogger implements LoggerInterface
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
