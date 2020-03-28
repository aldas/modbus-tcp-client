<?php

use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Packet\ModbusFunction\ReadWriteMultipleRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadWriteMultipleRegistersResponse;
use ModbusTcpClient\Packet\ResponseFactory;
use ModbusTcpClient\Utils\Types;

require __DIR__ . '/../vendor/autoload.php';

$connection = BinaryStreamConnection::getBuilder()
    ->setPort(5020)
    ->setHost('127.0.0.1')
    ->build();

$readStartAddress = 12288;
$readQuantity = 2;

$writeStartAddress = 12288;
$writeRegisters = [
    Types::toInt16(10), //000a as word
    Types::toInt16(-1000), //hex: FC18 as word
];
$packet = new ReadWriteMultipleRegistersRequest(
    $readStartAddress,
    $readQuantity,
    $writeStartAddress,
    $writeRegisters
);
echo 'Packet to be sent (in hex): ' . $packet->toHex() . PHP_EOL;

try {
    $binaryData = $connection->connect()->sendAndReceive($packet);
    echo 'Binary received (in hex):   ' . unpack('H*', $binaryData)[1] . PHP_EOL;

    /* @var $response ReadWriteMultipleRegistersResponse */
    $response = ResponseFactory::parseResponseOrThrow($binaryData);
    echo 'Parsed packet (in hex):     ' . $response->toHex() . PHP_EOL;

    // set internal index to match read start address to simplify array access
    $responseWithStartAddress = $response->withStartAddress($readStartAddress);
    print_r($responseWithStartAddress->getWordAt($readStartAddress)->getInt16());
    print_r($responseWithStartAddress[$readStartAddress + 1]->getInt16()); // use array access to get value

} catch (Exception $exception) {
    echo 'An exception occurred' . PHP_EOL;
    echo $exception->getMessage() . PHP_EOL;
    echo $exception->getTraceAsString() . PHP_EOL;
} finally {
    $connection->close();
}
