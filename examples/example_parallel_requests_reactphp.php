<?php

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/logger.php';

use ModbusTcpClient\Composer\Read\ReadRegistersBuilder;
use ModbusTcpClient\Composer\Read\Register\ReadRegisterRequest;
use ModbusTcpClient\Utils\Packet;

$fc3 = ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
    ->bit(256, 15, 'pump2_feedbackalarm_do')
    ->bit(256, 3, 'pump3_overload_alarm_do')
    ->byte(257, true, 'direction')
    // will be split into 2 requests as 1 request can return only range of 124 registers max
    ->int16(657, 'battery3_voltage_wo')
    ->uint16(658, 'wind_angle_wo')
    ->int32(659, 'gps_speed')
    ->uint32(661, 'distance_total_wo')
    ->uint64(663, 'gen2_energyw0_wo')
    ->float(667, 'longitude')
    ->string(669, 10, 'username')
    // will be another request as uri is different for subsequent string register
    ->useUri('tcp://127.0.0.1:5023')
    ->string(669, 10, 'username_plc2', function ($value) {
        return 'prefix_' . $value; // transform value after extraction
    })
    ->build(); // returns array of 3 requests

requestWithReactPhp($fc3);

/**
 * This will do 'parallel' socket request with help of ReactPHP socket library (https://github.com/reactphp/socket)
 * Install dependency with 'composer require react/socket:^1.11'
 *
 * NB: install PHP extension ('ev', 'event' or 'uv') if the concurrent socket connections are more than 1024.
 *
 * @param ReadRegisterRequest[] $requests
 */
function requestWithReactPhp(array $requests)
{
    $logger = new EchoLogger();
    $loop = React\EventLoop\Loop::get();

    $promises = [];
    foreach ($requests as $request) {
        $promise = new React\Promise\Deferred();
        $promises[] = $promise->promise();

        $connector = new React\Socket\Connector($loop, array(
            'dns' => false,
            'timeout' => 0.2
        ));

        $connector->connect($request->getUri())->then(
            function (React\Socket\ConnectionInterface $connection) use ($request, $promise, $logger) {
                $receivedData = b'';

                $logger->debug("sending: " . unpack('H*', $request)[1]);
                $connection->write($request);

                // wait for response event
                $connection->on('data', function ($data) use ($connection, $promise, $request, &$receivedData, $logger) {
                    $logger->debug("received: " . unpack('H*', $data)[1]);

                    // there are rare cases when MODBUS packet is received by multiple fragmented TCP packets and it could
                    // take PHP multiple reads from stream to get full packet. So we concatenate data and check if all that
                    // we have received makes a complete modbus packet.
                    // NB: `Packet::isCompleteLength` is suitable only for modbus TCP packets
                    $receivedData .= $data;
                    if (Packet::isCompleteLength($receivedData)) {
                        $logger->debug("complete packet: " . unpack('H*', $receivedData)[1]);

                        $promise->resolve($request->parse($receivedData));
                        $connection->end();
                    }
                });
                $connection->on('error', function ($data) use ($connection, $promise, $logger) {
                    $logger->debug("error data: " . unpack('H*', $data)[1]);

                    $promise->reject('Request failed: ' . print_r($data, true));
                    $connection->end();
                });
            },
            function (Exception $error) use ($promise) {
                $promise->reject('could not connect to uri: ' . $error->getMessage());
            });
    }

    React\Promise\all($promises)->then(
        function ($values) {
            echo 'All resolved:' . PHP_EOL;
            var_dump($values);
        },
        function ($reason) {
            echo 'Rejected:' . PHP_EOL;
            var_dump($reason);
        }
    )->always(function () use ($loop) {
        $loop->stop();
    });

    $loop->run();
}
