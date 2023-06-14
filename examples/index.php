<?php
require __DIR__ . '/../vendor/autoload.php';

use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersRequest;
use ModbusTcpClient\Packet\ResponseFactory;
use ModbusTcpClient\Packet\RtuConverter;
use ModbusTcpClient\Utils\Endian;
use ModbusTcpClient\Utils\Packet;

// To allow Nginx/Apache to read that device add following udev rule
// echo 'KERNEL=="ttyUSB0", GROUP="www-data", MODE="0660"' | sudo tee /etc/udev/rules.d/60-ttyusb-acl.rules
// sudo udevadm control --reload-rules && sudo udevadm trigger
$deviceURI = '/dev/ttyUSB0'; // do not make this changeable from WEB. This could be serious security risk.
$isSerialDevice = false; // change to true to enable reading serial devices. this will disable ip/port logic and uses RTU
if (getenv('MODBUS_SERIAL_ENABLED')) {
    // can be set from Nginx/Apache fast-cgi conf
    // for Nginx add these lines where you PHP is configured:
    //     fastcgi_param MODBUS_SERIAL_ENABLED true;
    //     fastcgi_param MODBUS_SERIAL_DEVICE /dev/ttyUSB0;
    $isSerialDevice = filter_var(getenv('MODBUS_SERIAL_ENABLED'), FILTER_VALIDATE_BOOLEAN);
    if ($isSerialDevice && getenv('MODBUS_SERIAL_DEVICE')) {
        $deviceURI = getenv('MODBUS_SERIAL_DEVICE');
    }
}
if ($isSerialDevice && stripos(PHP_OS, 'WIN') === 0) {
    echo 'Serial usb example can not be run on Windows!' . PHP_EOL;
    exit(0);
}

// if you want to let others specify their own ip/ports for querying data create file named '.allow-change' in this directory
// NB: this is a potential security risk!!!
$canChangeIpPort = !$isSerialDevice && file_exists('.allow-change');
$ip = '192.168.100.1';
$port = 502;
if ($canChangeIpPort) {
    $ip = filter_var($_GET['ip'] ?? '', FILTER_VALIDATE_IP) ? $_GET['ip'] : $ip;
    $port = (int)($_GET['port'] ?? $port);
}

$returnJson = filter_var($_GET['json'] ?? false, FILTER_VALIDATE_BOOLEAN);
$isRTU = $isSerialDevice || filter_var($_GET['rtu'] ?? false, FILTER_VALIDATE_BOOLEAN);
$fc = (int)($_GET['fc'] ?? 3);
$unitId = (int)($_GET['unitid'] ?? 0);
$startAddress = (int)($_GET['address'] ?? 256);
$quantity = (int)($_GET['quantity'] ?? 12);
$endianess = (int)($_GET['endianess'] ?? Endian::BIG_ENDIAN_LOW_WORD_FIRST);
Endian::$defaultEndian = $endianess;

$log = [];

$builder = BinaryStreamConnection::getBuilder()
    ->setConnectTimeoutSec(1.5) // timeout when establishing connection to the server
    ->setWriteTimeoutSec(1.0) // timeout when writing/sending packet to the server
    ->setReadTimeoutSec(1.0); // timeout when waiting response from server

$protocolType = "Modbus TCP";
if ($isRTU) {
    $protocolType = "Modbus RTU";
    $builder->setIsCompleteCallback(static function ($binaryData, $streamIndex): bool {
        return Packet::isCompleteLengthRTU($binaryData);
    });
}

if ($isSerialDevice) {
    $log[] = "Using: {$protocolType} function code: {$fc}, device: {$deviceURI}, address: {$startAddress}, quantity: {$quantity}, endianess: {$endianess}";
    $builder->setUri($deviceURI)
        ->setProtocol('serial')
        // delay this is crucial for some serial devices and delay needs to be long as 100ms (depending on the quantity)
        // or you will experience read errors ("stream_select interrupted") or invalid CRCs
        ->setDelayRead(100_000); // 100 milliseconds
} else {
    $log[] = "Using: {$protocolType} function code: {$fc}, ip: {$ip}, port: {$port}, address: {$startAddress}, quantity: {$quantity}, endianess: {$endianess}";
    $builder->setPort($port)->setHost($ip);
}

$connection = $builder->build();

if ($fc === 4) {
    $packet = new ReadInputRegistersRequest($startAddress, $quantity, $unitId);
} else {
    $fc = 3;
    $packet = new ReadHoldingRegistersRequest($startAddress, $quantity, $unitId);
}
if ($isRTU) {
    $packet = RtuConverter::toRtu($packet);
    $log[] = 'Modbus RTU Packet to be sent (in hex): ' . unpack('H*', $packet)[1];
} else {
    $log[] = 'Modbus TCP Packet to be sent (in hex): ' . $packet->toHex();
}

$startTime = round(microtime(true) * 1000, 3);
$result = [];
try {
    $binaryData = $connection->connect()->sendAndReceive($packet);

    $log[] = 'Binary received (in hex):   ' . unpack('H*', $binaryData)[1];

    /** @var $response ReadHoldingRegistersResponse */
    if ($isRTU) {
        $response = RtuConverter::fromRtuOrThrow($binaryData);
    } else {
        $response = ResponseFactory::parseResponseOrThrow($binaryData);
    }
    $response = $response->withStartAddress($startAddress);

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
            try {
                $double = $quadWord->getDouble();
            } catch (Exception $e) {
                $double = '-';
            }

        }

        $highByteAsInt = $word->getHighByteAsInt();
        $lowByteAsInt = $word->getLowByteAsInt();
        $result[$address] = [
            'highByte' => '0x' . str_pad(dechex($highByteAsInt), 2, '0', STR_PAD_LEFT) . ' / ' . $highByteAsInt . ' / "&#' . $highByteAsInt . ';"',
            'lowByte' => '0x' . str_pad(dechex($lowByteAsInt), 2, '0', STR_PAD_LEFT) . ' / ' . $lowByteAsInt . ' / "&#' . $lowByteAsInt . ';"',
            'highByteBits' => sprintf('%08d', decbin($highByteAsInt)),
            'lowByteBits' => sprintf('%08d', decbin($lowByteAsInt)),
            'int16' => $word->getInt16(),
            'UInt16' => $word->getUInt16(),
            'int32' => $doubleWord ? $doubleWord->getInt32() : null,
            'UInt32' => $doubleWord ? $doubleWord->getUInt32() : null,
            'float' => $doubleWord ? $doubleWord->getFloat() : null,
            'double' => $quadWord ? $double : null,
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
$elapsed = round(microtime(true) * 1000) - $startTime;

if ($returnJson) {
    header('Access-Control-Allow-Origin: *');
    header('Content-Type: application/json');
    http_response_code($result !== null ? 200 : 500);
    echo json_encode(
        [
            'data' => $result,
            'debug' => $log,
            'time_ms' => $elapsed
        ],
        JSON_PRETTY_PRINT
    );

    exit(0);
}

?>

<h2>Example Modbus TCP/RTU FC3/FC4 request</h2>
<form>
    Modbus TCP or RTU: <select name="rtu"<?php if ($isSerialDevice) {
        echo ' disabled';
    } ?>>
        <option value="0" <?php if (!$isRTU) {
            echo 'selected';
        } ?>>Modbus TCP
        </option>
        <option value="1" <?php if ($isRTU) {
            echo 'selected';
        } ?>>Modbus RTU
        </option>
    </select><br>
    Function code: <select name="fc">
        <option value="3" <?php if ($fc === 3) {
            echo 'selected';
        } ?>>Read Holding Registers (FC=03)
        </option>
        <option value="4" <?php if ($fc === 4) {
            echo 'selected';
        } ?>>Read Input Registers (FC=04)
        </option>
    </select><br>
    <?php if ($isSerialDevice) {
        echo "Device: {$deviceURI}<br>";
    } else {
        echo "IP: <input type=\"text\" name=\"ip\" value=\"{$ip}\"";
        if (!$canChangeIpPort) {
            echo ' disabled';
        }
        echo "><br>";
        echo "Port: <input type=\"number\" name=\"port\" value=\"{$port}\"><br>";
    } ?>
    UnitID (SlaveID): <input type="number" min="0" max="247" name="unitid" value="<?php echo $unitId; ?>"><br>
    Address: <input type="number" name="address" value="<?php echo $startAddress; ?>"> (NB: does your modbus server
    documentation uses
    `0` based addressing or `1` based?)<br>
    Quantity: <input type="number" min="1" max="124" name="quantity" value="<?php echo $quantity; ?>"><br>
    Endianess: <select name="endianess">
        <option value="1" <?php if ($endianess === 1) {
            echo 'selected';
        } ?>>BIG_ENDIAN
        </option>
        <option value="5" <?php if ($endianess === 5) {
            echo 'selected';
        } ?>>BIG_ENDIAN_LOW_WORD_FIRST
        </option>
        <option value="2" <?php if ($endianess === 2) {
            echo 'selected';
        } ?>>LITTLE_ENDIAN
        </option>
        <option value="6" <?php if ($endianess === 6) {
            echo 'selected';
        } ?>>LITTLE_ENDIAN_LOW_WORD_FIRST
        </option>
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
        <td>double</td>
        <td>int64</td>
        <td>UInt64</td>
    </tr>
    <?php foreach ($result ?? [] as $address => $values) { ?>
        <tr>
            <td><?php echo $address ?></td>
            <td><?php echo implode('</td><td>', $values) ?></td>
        </tr>
    <?php } ?>
</table>
Time <?php echo $elapsed ?> ms
</br>
Page generated: <?php echo date('c') ?>
