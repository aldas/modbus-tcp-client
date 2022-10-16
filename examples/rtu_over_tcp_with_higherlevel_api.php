<?php

use ModbusTcpClient\Composer\Read\ReadRegistersBuilder;
use ModbusTcpClient\Composer\Read\Register\ReadRegisterRequest;
use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Packet\RtuConverter;
use ModbusTcpClient\Utils\Packet;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/logger.php';

// Modbus server simulator https://www.modbusdriver.com/diagslave.html
// Start simulator with `./diagslave -m enc -a 1 -p 5020`

$connection = BinaryStreamConnection::getBuilder()
    ->setPort(5020)
    ->setHost('127.0.0.1')
    ->setReadTimeoutSec(0.5)
    ->setIsCompleteCallback(function ($binaryData, $streamIndex) {
        return Packet::isCompleteLengthRTU($binaryData);
    })
    ->setLogger(new EchoLogger())
    ->build();

$unitID = 1; // RTU packet slave id equivalent is Modbus TCP unitId
$fc3requests = ReadRegistersBuilder::newReadHoldingRegisters('no_address', $unitID) // uri/address does not matter because we use $connection
    ->int16(1, 'address1_value') // or whatever data type that value is in that register
    ->uint16(2, 'address2_value')
    // See `ReadRegistersBuilder.php` for available data type methods
    ->build(); // returns array of ReadHoldingRegistersRequest requests

try {
    /** @var $request ReadRegisterRequest */
    foreach ($fc3requests as $request) {
        echo 'Packet to be sent (in hex): ' . $request->getRequest()->toHex() . PHP_EOL;
        $rtuPacket = RtuConverter::toRtu($request->getRequest());

        $binaryData = $connection->connect()->sendAndReceive($rtuPacket);
        echo 'RTU Binary received (in hex):   ' . unpack('H*', $binaryData)[1] . PHP_EOL;

        $tcpResponsePacket = RtuConverter::fromRtu($binaryData);

        echo 'Data parsed from packet (bytes):' . PHP_EOL;
        $result = $request->parse($tcpResponsePacket);
        print_r($result);
    }
} catch (Exception $exception) {
    echo 'An exception occurred' . PHP_EOL;
    echo $exception->getMessage() . PHP_EOL;
    echo $exception->getTraceAsString() . PHP_EOL;
} finally {
    $connection->close();
}
