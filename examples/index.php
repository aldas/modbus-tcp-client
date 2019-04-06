<?php
require __DIR__ . '/../vendor/autoload.php';

use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Packet\ResponseFactory;
use ModbusTcpClient\Utils\Endian;

$returnJson = filter_var($_GET['json'] ?? false, FILTER_VALIDATE_BOOLEAN);

// if you want to let others specify their own ip/ports for querying data create file named '.allow-change' in this directory
// NB: this is a potential security risk!!!
$canChangeIpPort = file_exists('.allow-change');

$ip = '192.168.100.1';
$port = 502;
if ($canChangeIpPort) {
    $ip = filter_var($_GET['ip'], FILTER_VALIDATE_IP) ? $_GET['ip'] : $ip;
    $port = (int)($_GET['port'] ?? $port);
}

$unitId = (int)($_GET['unitid'] ?? 0);
$address = (int)($_GET['address'] ?? 256);
$quantity = (int)($_GET['quantity'] ?? 12);
$endianess = (int)($_GET['endianess'] ?? Endian::BIG_ENDIAN_LOW_WORD_FIRST);
Endian::$defaultEndian = $endianess;

$log = [];
$log[] = "Using: ip: {$ip}, port: {$port}, address: {$address}, quantity: {$quantity}, endianess: {$endianess}";

$connection = BinaryStreamConnection::getBuilder()
    ->setPort($port)
    ->setHost($ip)
    ->build();


$packet = new ReadHoldingRegistersRequest($address, $quantity, $unitId);
$log[] = 'Packet to be sent (in hex): ' . $packet->toHex();

$result = [];
try {
    $binaryData = $connection->connect()->sendAndReceive($packet);
    $log[] = 'Binary received (in hex):   ' . unpack('H*', $binaryData)[1];

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
            try {
                $Int64 = $quadWord->getInt64();
            } catch (Exception $e) {
                $Int64 = '-';
            }

        }

        $highByteAsInt = $word->getHighByteAsInt();
        $lowByteAsInt = $word->getLowByteAsInt();
        $result[$address] = [
            'highByte' => '0x' . str_pad(dechex($highByteAsInt), 2, '0') . ' / ' . $highByteAsInt . ' / "&#' . $highByteAsInt . ';"',
            'lowByte' => '0x' . str_pad(dechex($lowByteAsInt), 2, '0') . ' / ' . $lowByteAsInt . ' / "&#' . $lowByteAsInt . ';"',
            'highByteBits' => sprintf('%08d', decbin($highByteAsInt)),
            'lowByteBits' => sprintf('%08d', decbin($lowByteAsInt)),
            'int16' => $word->getInt16(),
            'UInt16' => $word->getUInt16(),
            'int32' => $doubleWord ? $doubleWord->getInt32() : null,
            'UInt32' => $doubleWord ? $doubleWord->getUInt32() : null,
            'float' => $doubleWord ? $doubleWord->getFloat() : null,
            'Int64' => $quadWord ? $Int64 : null,
            'UInt64' => $quadWord ? $UInt64 : null,
        ];
    }

} catch (Exception $exception) {
    $result = null;
    $log[] = 'An exception occurred';
    $log[] = $exception->getMessage();
    $log[] = $exception->getTraceAsString();
} finally {
    $connection->close();
}

if ($returnJson) {
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    http_response_code($result !== null ? 200 : 500);
    echo json_encode(
        [
            'data' => $result,
            'debug' => $log
        ],
        JSON_PRETTY_PRINT
    );

    exit(0);
}

?>

<h2>Example FC3 request</h2>
<form>
    IP: <input type="text" name="ip" value="<?php echo $ip; ?>" <?php if (!$canChangeIpPort) { echo 'disabled'; } ?>><br>
    Port: <input type="number" name="port" value="<?php echo $port; ?>"><br>
    Address: <input type="number" name="address" value="<?php echo $address; ?>"><br>
    Quantity: <input type="number" name="quantity" value="<?php echo $quantity; ?>"><br>
    Endianess: <select name="endianess">
        <option value="1" <?php if ($endianess === 1) { echo 'selected'; } ?>>BIG_ENDIAN</option>
        <option value="5" <?php if ($endianess === 5) { echo 'selected'; } ?>>BIG_ENDIAN_LOW_WORD_FIRST</option>
        <option value="2" <?php if ($endianess === 2) { echo 'selected'; } ?>>LITTLE_ENDIAN</option>
        <option value="6" <?php if ($endianess === 6) { echo 'selected'; } ?>>LITTLE_ENDIAN_LOW_WORD_FIRST</option>
    </select><br>
    <button type="submit">Send</button>
</form>
<h2>Debug info</h2>
<pre>
<?php
foreach ($log as $m) {
    echo $m . PHP_EOL;
}
?>
</pre>
<h2>Result</h2>
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
        <td>int32</td>
        <td>UInt32</td>
        <td>float</td>
        <td>int64</td>
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
