<?php

use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleCoilRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleCoilResponse;
use ModbusTcpClient\Packet\ResponseFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/logger.php';

$connection = BinaryStreamConnection::getBuilder()
    ->setPort(5020)
    ->setHost('127.0.0.1')
    ->setLogger(new EchoLogger())
    ->build();

$startAddress = 12288;
$coil = true;
$unitID = 0;
$packet = new WriteSingleCoilRequest($startAddress, $coil, $unitID); // NB: This is Modbus TCP packet not Modbus RTU over TCP!
echo 'Packet to be sent (in hex): ' . $packet->toHex() . PHP_EOL;

try {
    $binaryData = $connection->connect()
        ->sendAndReceive($packet);
    echo 'Binary received (in hex):   ' . unpack('H*', $binaryData)[1] . PHP_EOL;

    /* @var $response WriteSingleCoilResponse */
    $response = ResponseFactory::parseResponseOrThrow($binaryData);
    echo 'Parsed packet (in hex):     ' . $response->toHex() . PHP_EOL;
    echo 'Coil value parsed from packet:' . PHP_EOL;
    print_r($response->isCoil());

} catch (Exception $exception) {
    echo 'An exception occurred' . PHP_EOL;
    echo $exception->getMessage() . PHP_EOL;
    echo $exception->getTraceAsString() . PHP_EOL;
} finally {
    $connection->close();
}
