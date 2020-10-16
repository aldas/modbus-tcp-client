<?php

require __DIR__ . '/../vendor/autoload.php';

use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Composer\Read\ReadRegistersBuilder;
use ModbusTcpClient\Composer\Read\Register\ReadRegisterRequest;
use ModbusTcpClient\Utils\Packet;
use function Amp\Socket\connect;

// Install dependency with 'composer require amphp/socket:^1.1'

$registers = [
    ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'bit', 'address' => 256, 'bit' => 15, 'name' => 'pump2_feedbackalarm_do'],
    ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'bit', 'address' => 256, 'bit' => 3, 'name' => 'pump3_overload_alarm_do'],
    ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'byte', 'address' => 257, 'name' => 'direction'],
    // will be split into 2 requests as 1 request can return only range of 124 registers max
    [
        'uri' => 'tcp://127.0.0.1:5022',
        'type' => 'int16',
        'address' => 657,
        'name' => 'battery3_voltage_wo',
        'callback' => function ($value, $address, $response) {
            return 'prefix_' . $value; // transform value after extraction
        },
        'errorCallback' => function (\Exception $exception, Address $address, $response) {
            // optional: callback called then extraction failed with an error
            return $address->getType() === Address::TYPE_STRING ? '' : null; // does not make sense but gives you an idea
        }
    ],
    ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'uint16', 'address' => 658, 'name' => 'wind_angle_wo'],
    ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'int32', 'address' => 659, 'name' => 'gps_speed'],
    ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'uint32', 'address' => 661, 'name' => 'distance_total_wo'],
    ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'uint64', 'address' => 663, 'name' => 'gen2_energyw0_wo'],
    ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'float', 'address' => 667, 'name' => 'longitude'],
    ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'string', 'address' => 669, 'length' => 10, 'name' => 'username'],
    // will be another request as uri is different for subsequent string register
    ['uri' => 'tcp://192.168.100.1:5023', 'type' => 'string', 'address' => 669, 'length' => 10, 'name' => 'username_plc2'],
];
$fc3RequestsFromArray = ReadRegistersBuilder::newReadHoldingRegisters()
    ->allFromArray($registers)
    ->build();

requestWithAmp($fc3RequestsFromArray);

/**
 * This will do 'parallel' socket request with help of Amp socket library (https://amphp.org/socket/)
 * Install dependency with 'composer require amphp/socket:^1.1'
 *
 * NB: install PHP extension ('ev', 'event' or 'uv') if the concurrent socket connections are more than 1024.
 *
 *
 * @param ReadRegisterRequest[] $requests
 */
function requestWithAmp(array $requests)
{
    $promises = [];
    foreach ($requests as $request) {
        $promises[] = Amp\call(function () use ($request) {
            /** @var \Amp\Socket\Socket $socket */
            $socket = yield connect($request->getUri());
            try {
                yield $socket->write($request);

                $data = b'';
                while (null !== $chunk = yield $socket->read()) {
                    // there are rare cases when MODBUS packet is received by multiple fragmented TCP packets and it could
                    // take PHP multiple reads from stream to get full packet. So we concatenate data and check if all that
                    // we have received makes a complete modbus packet.
                    // NB: `Packet::isCompleteLength` is suitable only for modbus TCP packets
                    $data .= $chunk;
                    if (Packet::isCompleteLength($data)) {
                        return $request->parse($data);
                    }
                }

            } finally {
                $socket->close();
            }

            return null;
        });
    }

    try {
        // will run multiple request in parallel using non-blocking php stream io
        $responses = Amp\Promise\wait(Amp\Promise\all($promises));
        print_r($responses);
    } catch (Throwable $e) {
        print_r($e);
    }
}
