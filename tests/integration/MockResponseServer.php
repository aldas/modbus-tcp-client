<?php

namespace Test\integration;

use React\Datagram\Socket;
use React\EventLoop\Factory;
use React\Socket\Server;

require __DIR__ . '/../../vendor/autoload.php';

class MockResponseServer
{
    const MAX_WAIT_TIMEOUT = 5;
    private $port;

    public function __construct($protocol, $port, $answerTimeout, $responsePacket)
    {
        $this->port = $port;

        $loop = Factory::create();

        if ('TCP' === $protocol) {
            $this->startTcpServer($loop, $answerTimeout, $responsePacket);
        } else {
            $this->startUdpServer($loop, $answerTimeout, $responsePacket);
        }

        $loop->addPeriodicTimer(self::MAX_WAIT_TIMEOUT, function () use ($loop) {
            $loop->stop();
        });
        $loop->run();
    }

    private function startTcpServer($loop, $answerTimeout, $responsePacket)
    {
        $socket = new Server($loop);

        $socket->on('connection', function ($conn) use ($socket, $loop, $answerTimeout, $responsePacket) {
            $conn->on('data', function ($data) use ($conn, $answerTimeout, $responsePacket) {
                if ($answerTimeout) {
                    sleep($answerTimeout);
                }
                $conn->write(pack('H*', $responsePacket));

                echo unpack('H*', $data)[1];
            });

            $conn->on('close', function () use ($socket, $loop) {
                $socket->shutdown();
                $loop->stop();
            });
        });

        $socket->listen($this->port, getenv('MOCKSERVER_BIND_ADDRESS') ?: '127.0.0.1');
    }

    private function startUdpServer($loop, $answerTimeout, $responsePacket)
    {
        $factory = new \React\Datagram\Factory($loop);

        $address = (getenv('MOCKSERVER_BIND_ADDRESS') ?: '127.0.0.1') . ':' . $this->port;
        $factory->createServer($address)->then(function (Socket $server) use ($loop, $answerTimeout, $responsePacket) {
            $server->on('message', function ($message, $address, Socket $server) use ($loop, $answerTimeout, $responsePacket) {
                if ($answerTimeout > 0) {
                    sleep($answerTimeout);
                }
                $server->send(pack('H*', $responsePacket), $address);

                echo unpack('H*', $message)[1];

                $loop->addTimer(0.001, function () use ($server, $loop) {
                    $server->emit('shutdown', [$server]);
                });
            });

            //silly but otherwise client will not receive packets from server. probably server is closed before stream is flushed etc
            $server->on('shutdown', function () use ($server, $loop) {
                $loop->addTimer(0.002, function () use ($server, $loop) {
                    $server->close();
                    $loop->stop();
                });
            });
        });
    }
}

new MockResponseServer($argv[1], $argv[2], $argv[3], $argv[4]);