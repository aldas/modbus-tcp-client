<?php

require __DIR__ . '/../vendor/autoload.php';

use ModbusTcpClient\Composer\Read\ReadRegistersBuilder;
use ModbusTcpClient\Composer\Read\ReadRequest;

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
 * Install dependency with 'composer require react/socket:^0.8.11'
 *
 *
 * @param ReadRequest[] $requests
 */
function requestWithReactPhp(array $requests)
{
    $loop = React\EventLoop\Factory::create();

    $promises = [];
    foreach ($requests as $request) {
        $promise = new React\Promise\Deferred();
        $promises[] = $promise->promise();

        $connector = new React\Socket\Connector($loop, array(
            'dns' => false,
            'timeout' => 0.2
        ));

        $connector->connect($request->getUri())->then(
            function (React\Socket\ConnectionInterface $connection) use ($request, $promise) {
                $connection->write($request);

                // wait for response event
                $connection->on('data', function ($data) use ($connection, $promise, $request) {
                    $promise->resolve($request->parse($data));
                    $connection->end();
                });
                $connection->on('error', function ($data) use ($connection, $promise) {
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
