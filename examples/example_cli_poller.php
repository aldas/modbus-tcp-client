<?php

if (php_sapi_name() !== 'cli') {
    echo 'Should be used only in command line interface';
    return;
}

require __DIR__ . '/../vendor/autoload.php';

use ModbusTcpClient\Composer\Read\ReadRegistersBuilder;
use ModbusTcpClient\Utils\Packet;

$requests = ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
    ->bit(278, 5, 'dirchange1_status')
    ->int16(256, 'room2_temp_wo')
    // will be split into 2 requests as 1 request can return only range of 124 registers max
    ->uint16(453, 'gen1_fuel_rate_wo')
    // will be another request as uri is different for subsequent string register
    ->useUri('tcp://127.0.0.1:5023')
    ->int16(270, 'room7_temp_wo', function ($value) {
        return $value ? $value / 10 : $value; // transform value after extraction
    })
    ->build(); // returns array of 3 requests

// Install: 'composer require react/socket:^1.11'
// NB: install PHP extension ('ev', 'event' or 'uv') if the concurrent socket connections are more than 1024.

$loop = React\EventLoop\Loop::get();

$n = 60;
$loop->addPeriodicTimer(1.0, function () use ($loop, &$n, $requests) {
    echo microtime(true) . ": TICK! remaining {$n} seconds" . PHP_EOL;

    foreach ($requests as $request) {
        $connector = new React\Socket\Connector($loop, array(
            'dns' => false,
            'timeout' => 0.2
        ));

        $connector->connect($request->getUri())->then(
            function (React\Socket\ConnectionInterface $connection) use ($request) {
                $receivedData = b'';

                echo microtime(true) . ": connected to {$request->getUri()}" . PHP_EOL;
                $connection->write($request);

                // wait for response event
                $connection->on('data', function ($data) use ($connection, $request, &$receivedData) {
                    // there are rare cases when MODBUS packet is received by multiple fragmented TCP packets and it could
                    // take PHP multiple reads from stream to get full packet. So we concatenate data and check if all that
                    // we have received makes a complete modbus packet.
                    // NB: `Packet::isCompleteLength` is suitable only for modbus TCP packets
                    $receivedData .= $data;
                    if (Packet::isCompleteLength($receivedData)) {
                        echo microtime(true) . ": uri: {$request->getUri()}, complete response: " . print_r($request->parse($receivedData), true) . PHP_EOL;
                        $connection->end();
                    } else {
                        echo microtime(true) . ": uri: {$request->getUri()}, partial response: " . print_r($data, true) . PHP_EOL;
                    }
                });
                $connection->on('error', function ($data) use ($connection, $request) {
                    echo microtime(true) . ": uri: {$request->getUri()}, Error during connection! error: " . print_r($data, true) . PHP_EOL;
                    $connection->end();
                });
            },
            function (Exception $error) use ($request) {
                echo microtime(true) . ": uri: {$request->getUri()}, failed to connect! error: " . $error->getMessage() . PHP_EOL;
            });
    }

    if ($n <= 0) {
        $loop->stop();
    }
    $n--;
});

$loop->run();
