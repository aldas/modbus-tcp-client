<?php

use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersResponse;
use ModbusTcpClient\Packet\RtuConverter;

require __DIR__ . '/../vendor/autoload.php';

$connection = BinaryStreamConnection::getBuilder()
    ->setUri('/dev/ttyUSB0')
    ->setProtocol('serial')
    ->setIsCompleteCallback(static function ($binaryData, $streamIndex): bool {
        // NB: returning always true could lead to problems with reading fragmented packets (so we returning too early)
        // safest way would be to calculate bytes length that we expect and error response length.
        // Example for error: 5 bytes = 1 unit id + 1 byte for function code + 1 byte for error code + 2 bytes for CRC
        // Example for FC4 with quantity 2: 8 bytes = 1 unit id + 1 byte for function code + 2 bytes start address + 2 * quantity
        return true;
    })
    ->build();

$startAddress = 1;
$quantity = 2;
$slaveId = 1; // RTU packet slave id equivalent is Modbus TCP unitId

$tcpPacket = new ReadInputRegistersRequest($startAddress, $quantity, $slaveId);
$rtuPacket = RtuConverter::toRtu($tcpPacket);

try {
    echo 'RTU Binary to sent (in hex):   ' . unpack('H*', $rtuPacket)[1] . PHP_EOL;

    $start = microtime(true);
    $binaryData = $connection->connect()->sendAndReceive($rtuPacket);
    $end = (microtime(true) - $start) * 1000;
    echo 'Response in: ' . $end . ' ms' . PHP_EOL;

    echo 'RTU Binary received (in hex):   ' . unpack('H*', $binaryData)[1] . PHP_EOL;

    /** @var ReadInputRegistersResponse $response */
    $response = RtuConverter::fromRtu($binaryData)->withStartAddress($startAddress);
    echo 'Parsed packet (in hex):     ' . $response->toHex() . PHP_EOL;

    echo PHP_EOL;
    echo 'Temperature: ' . ($response->getWordAt(1)->getInt16() / 10) . PHP_EOL;
    echo 'Humidity: ' . ($response->getWordAt(2)->getInt16() / 10) . PHP_EOL;

} catch (Exception $exception) {
    echo 'An exception occurred' . PHP_EOL;
    echo $exception->getMessage() . PHP_EOL;
    echo $exception->getTraceAsString() . PHP_EOL;
} finally {
    $connection->close();
}
