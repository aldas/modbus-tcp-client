<?php

namespace ModbusTcpClient\Network;

class SerialMockData
{
    public static $exec = null;
    public static $execCounter = 0;
    public static $execOpts = [];

    public static $fopen = null;
    public static $fopenCounter = 0;
    public static $fopenOpts = [];

}

function exec($command, array &$output = null, &$return_var = null)
{
    if (SerialMockData::$exec === null) {
        return \exec($command, $output, $return_var);
    }

    SerialMockData::$execOpts[] = $command;
    return SerialMockData::$exec[SerialMockData::$execCounter++];
}

function fopen($filename, $mode, $use_include_path = null, $context = null)
{
    if (SerialMockData::$fopen === null) {
        return \fopen($filename, $mode, $use_include_path, $context);
    }

    SerialMockData::$fopenOpts[] = $filename;
    return SerialMockData::$fopen[SerialMockData::$fopenCounter++];
}


namespace Tests\unit\Network;


use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Network\SerialMockData;
use ModbusTcpClient\Network\SerialStreamCreator;
use ModbusTcpClient\Network\StreamCreator;
use PHPUnit\Framework\TestCase;

class SerialStreamCreatorTest extends TestCase
{
    protected function tearDown()
    {
        SerialMockData::$exec = null;
        SerialMockData::$execCounter = 0;
        SerialMockData::$execOpts = [];

        SerialMockData::$fopen = null;
        SerialMockData::$fopenCounter = 0;
        SerialMockData::$fopenOpts = [];
    }

    public function testSerialStreamCreator()
    {
        SerialMockData::$exec = ['stty'];
        SerialMockData::$fopen = ['stream'];
        $creator = new SerialStreamCreator(['sttyModes' => ['cs8']]);

        $connection = BinaryStreamConnection::getBuilder()
            ->setProtocol(StreamCreator::TYPE_SERIAL)
            ->setUri('/dev/random')
            ->build();

        $stream = $creator->createStream($connection);

        $this->assertEquals('stream', $stream);
        $this->assertEquals(['stty -F /dev/random cs8'], SerialMockData::$execOpts);
    }

    /**
     * @expectedException \ModbusTcpClient\Network\IOException
     * @expectedExceptionMessage stty failed to configure device
     */
    public function testExecException()
    {
        SerialMockData::$exec = [false];
        SerialMockData::$fopen = ['stream'];
        $creator = new SerialStreamCreator(['sttyModes' => ['cs8']]);

        $connection = BinaryStreamConnection::getBuilder()
            ->setProtocol(StreamCreator::TYPE_SERIAL)
            ->setUri('/dev/random')
            ->build();

        $creator->createStream($connection);
    }

    /**
     * @expectedException \ModbusTcpClient\Network\IOException
     * @expectedExceptionMessage failed to open device
     */
    public function testFopenException()
    {
        SerialMockData::$exec = ['stty'];
        SerialMockData::$fopen = [false];
        $creator = new SerialStreamCreator(['sttyModes' => ['cs8']]);

        $connection = BinaryStreamConnection::getBuilder()
            ->setProtocol(StreamCreator::TYPE_SERIAL)
            ->setUri('/dev/random')
            ->build();

        $creator->createStream($connection);
    }

}
