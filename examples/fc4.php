<?php

use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersResponse;
use ModbusTcpClient\Packet\ResponseFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/logger.php';

$connection = BinaryStreamConnection::getBuilder()
    ->setPort(5020)
    ->setHost('127.0.0.1')
    ->setLogger(new EchoLogger())
    ->build();

$startAddress = 256;
$quantity = 6;
$unitID = 0;
$packet = new ReadInputRegistersRequest($startAddress, $quantity, $unitID); // NB: This is Modbus TCP packet not Modbus RTU over TCP!
echo 'Packet to be sent (in hex): ' . $packet->toHex() . PHP_EOL;

try {
    $binaryData = $connection->connect()
        ->sendAndReceive($packet);
    echo 'Binary received (in hex):   ' . unpack('H*', $binaryData)[1] . PHP_EOL;

    /**
     * @var $response ReadInputRegistersResponse
     */
    $response = ResponseFactory::parseResponseOrThrow($binaryData);
    echo 'Parsed packet (in hex):     ' . $response->toHex() . PHP_EOL;
    echo 'Data parsed from packet (bytes):' . PHP_EOL;
    print_r($response->getData());

    foreach ($response as $word) {
        print_r($word->getBytes());
    }
    foreach ($response->asDoubleWords() as $doubleWord) {
        print_r($doubleWord->getBytes());
    }

    // set internal index to match start address to simplify array access
    $responseWithStartAddress = $response->withStartAddress($startAddress);
    print_r($responseWithStartAddress[256]->getBytes()); // use array access to get word
    print_r($responseWithStartAddress->getDoubleWordAt(257)->getFloat());

} catch (Exception $exception) {
    echo 'An exception occurred' . PHP_EOL;
    echo $exception->getMessage() . PHP_EOL;
    echo $exception->getTraceAsString() . PHP_EOL;
} finally {
    $connection->close();
}
