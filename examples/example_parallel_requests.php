<?php

require __DIR__ . '/../vendor/autoload.php';

use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Composer\Read\ReadRegistersBuilder;
use ModbusTcpClient\Network\NonBlockingClient;

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
    ->string(
        669,
        10,
        'username_plc2',
        function ($value, $address, $response) {
            return 'prefix_' . $value; // optional: transform value after extraction
        },
        function (\Exception $exception, Address $address, $response) {
            // optional: callback called then extraction failed with an error
            return $address->getType() === Address::TYPE_STRING ? '' : null; // does not make sense but gives you an idea
        }
    )
    ->build(); // returns array of 3 requests

$responseContainer = (new NonBlockingClient(['readTimeoutSec' => 0.2]))->sendRequests($fc3);
print_r($responseContainer);
