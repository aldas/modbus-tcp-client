<?php
require __DIR__ . '/../vendor/autoload.php';

use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Packet\ResponseFactory;
use ModbusTcpClient\Utils\Endian;

// set to true if you want to let others specify their own ip/ports for querying data
// NB: this is a security risk!!!
$canChangeIpPort = false;

$ip = '192.168.100.1';
$port = 502;
if ($canChangeIpPort) {
    $ip = filter_var($_GET['ip'], FILTER_VALIDATE_IP) ? $_GET['ip'] : $ip;
    $port = ((int)$_GET['port']) ?: $port;
}

$unitId = ((int)$_GET['unitid']) ?: 0;
$address = ((int)$_GET['address']) ?: 256;
$quantity = ((int)$_GET['quantity']) ?: 12;
$endianess = ((int)$_GET['endianess']) ?: Endian::BIG_ENDIAN_LOW_WORD_FIRST;
Endian::$defaultEndian = $endianess;

echo "Using: ip: {$ip}, port: {$port}, address: {$address}, quantity: {$quantity}, endianess: {$endianess}<br>" . PHP_EOL;
$connection = BinaryStreamConnection::getBuilder()
    ->setPort($port)
    ->setHost($ip)
    ->build();


$packet = new ReadHoldingRegistersRequest($address, $quantity, $unitId);
echo 'Packet to be sent (in hex): ' . $packet->toHex() . '<br>' . PHP_EOL;

$result = [];
try {
    $binaryData = $connection->connect()->sendAndReceive($packet);
    echo 'Binary received (in hex):   ' . unpack('H*', $binaryData)[1] . '<br>' . PHP_EOL;

    /** @var $response ReadHoldingRegistersResponse */
    $response = ResponseFactory::parseResponseOrThrow($binaryData)->withStartAddress($address);

    foreach ($response as $address => $word) {
        $doubleWord = isset($response[$address + 1]) ? $response->getDoubleWordAt($address) : null;
        $quadWord = null;
        if (isset($response[$address + 3])) {
            $quadWord = $response->getQuadWordAt($address);
            try {
                $UInt64 = $quadWord->getUInt64(); // some data can not be converted to unsigned 64bit int due PHP memory limitations
            } catch (Exception $e) {
                $UInt64 = '-';
            }

        }

        $highByteAsInt = $word->getHighByteAsInt();
        $lowByteAsInt = $word->getLowByteAsInt();
        $result[$address] = [
            'highByte' => '0x' . str_pad(dechex($highByteAsInt), 2, '0') . ' / ' . $highByteAsInt . ' / "&#' . $highByteAsInt . ';"',
            'lowByte' => '0x' . str_pad(dechex($lowByteAsInt), 2, '0') . ' / ' . $lowByteAsInt . ' / "&#' . $lowByteAsInt . ';"',
            'highByteBits' => sprintf('%08d', decbin($highByteAsInt)),
            'lowByteBits' => sprintf('%08d', decbin($lowByteAsInt)),
            'UInt16' => $word->getUInt16(),
            'int16' => $word->getInt16(),
            'UInt32' => $doubleWord ? $doubleWord->getUInt32() : null,
            'int32' => $doubleWord ? $doubleWord->getInt32() : null,
            'float' => $doubleWord ? $doubleWord->getFloat() : null,
            'UInt64' => $quadWord ? $UInt64 : null,
        ];
    }

} catch (Exception $exception) {
    echo 'An exception occurred' . PHP_EOL;
    echo $exception->getMessage() . PHP_EOL;
    echo $exception->getTraceAsString() . PHP_EOL;
} finally {
    $connection->close();
}

?>

<table border="1">
    <tr>
        <td rowspan="2">WORD<br>address</td>
        <td colspan="6">Word</td>
        <td colspan="3">Double word (from this address)</td>
        <td>Quad word</td>
    </tr>
    <tr>
        <td>high byte<br>Hex / Dec / Ascii</td>
        <td>low byte<br>Hex / Dec / Ascii</td>
        <td>high bits</td>
        <td>low bits</td>
        <td>int16</td>
        <td>UInt16</td>
        <td>UInt32</td>
        <td>int32</td>
        <td>float</td>
        <td>UInt64</td>
    </tr>
    <?php foreach ($result as $address => $values) { ?>
        <tr>
            <td><?php echo $address ?></td>
            <td><?php echo implode('</td><td>', $values) ?></td>
        </tr>
    <?php } ?>
</table>
Page generated: <?php echo date('c') ?>
