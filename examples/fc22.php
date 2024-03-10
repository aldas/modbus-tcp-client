<?php

use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Packet\ModbusFunction\MaskWriteRegisterRequest;
use ModbusTcpClient\Packet\ModbusFunction\MaskWriteRegisterResponse;
use ModbusTcpClient\Packet\ResponseFactory;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/logger.php';

$connection = BinaryStreamConnection::getBuilder()
    ->setPort(5020)
    ->setHost('127.0.0.1')
    ->setLogger(new EchoLogger())
    ->build();

$readStartAddress = 12288;

$bitPosition = 2; // third bit (starts from 0)
$ANDMask = 0x0000; // 2 bytes
$ANDMask |= (1 << $bitPosition); // set the bit, set third bit to 1. $ANDMask = 0x0004, 0b00000100

$ORMask = 0x0007; // 2 bytes, bin = 0b00000111
$ORMask &= ~(1 << $bitPosition); // clear the bit, set third bit to 0. $ORMask = 0x0003, 0b00000011

$unitID = 1;
$packet = new MaskWriteRegisterRequest(
    $readStartAddress,
    $ANDMask,
    $ORMask,
    $unitID
); // NB: This is Modbus TCP packet not Modbus RTU over TCP!
echo 'Packet to be sent (in hex): ' . $packet->toHex() . PHP_EOL;

try {
    $binaryData = $connection->connect()->sendAndReceive($packet);
    echo 'Binary received (in hex):   ' . unpack('H*', $binaryData)[1] . PHP_EOL;

    /* @var $response MaskWriteRegisterResponse */
    $response = ResponseFactory::parseResponseOrThrow($binaryData);
    echo 'Parsed packet (in hex):     ' . $response->toHex() . PHP_EOL;

    print_r($response->getANDMask());
    print_r($response->getORMask());

} catch (Exception $exception) {
    echo 'An exception occurred' . PHP_EOL;
    echo $exception->getMessage() . PHP_EOL;
    echo $exception->getTraceAsString() . PHP_EOL;
} finally {
    $connection->close();
}
