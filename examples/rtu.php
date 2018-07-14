<?php

use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\RtuConverter;

require __DIR__ . '/../vendor/autoload.php';

$connection = BinaryStreamConnection::getBuilder()
    ->setPort(502)
    ->setHost('127.0.0.1')
    ->build();

$startAddress = 256;
$quantity = 6;
$slaveId = 1; // RTU packet slave id equivalent is Modbus TCP unitId

$rtuPacket = RtuConverter::toRtu(new ReadHoldingRegistersRequest($startAddress, $quantity, $slaveId));

try {
    $binaryData = $connection->connect()->sendAndReceive($rtuPacket);
    echo 'Binary received (in hex):   ' . unpack('H*', $binaryData)[1] . PHP_EOL;

    $response = RtuConverter::fromRtu($binaryData);
    echo 'Parsed packet (in hex):     ' . $response->toHex() . PHP_EOL;
    echo 'Data parsed from packet (bytes):' . PHP_EOL;
    print_r($response->getData());

} catch (Exception $exception) {
    echo 'An exception occurred' . PHP_EOL;
    echo $exception->getMessage() . PHP_EOL;
    echo $exception->getTraceAsString() . PHP_EOL;
} finally {
    $connection->close();
}
