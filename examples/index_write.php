<?php

use ModbusTcpClient\Network\BinaryStreamConnection;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleRegistersRequest;
use ModbusTcpClient\Packet\RtuConverter;
use ModbusTcpClient\Utils\Endian;
use ModbusTcpClient\Utils\Packet;
use ModbusTcpClient\Utils\Types;

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/logger.php';


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
    $ip = filter_var($_REQUEST['ip'] ?? '', FILTER_VALIDATE_IP) ? $_REQUEST['ip'] : $ip;
    $port = (int)($_REQUEST['port'] ?? $port);
}

$isRTU = $isSerialDevice || filter_var($_REQUEST['rtu'] ?? false, FILTER_VALIDATE_BOOLEAN);
$unitId = (int)($_REQUEST['unitid'] ?? 0);
$startAddress = (int)($_REQUEST['address'] ?? 100);

$value = ($_REQUEST['value'] ?? 0);
$dataType = ($_REQUEST['type'] ?? 0);
$endianess = (int)($_REQUEST['endianess'] ?? Endian::BIG_ENDIAN_LOW_WORD_FIRST);
Endian::$defaultEndian = $endianess;

$v = null;
$error = null;
switch ($dataType) {
    case 'uint8':
        $value = (int)$value;
        if ($value < 0 || $value > 255) {
            $error = "valid range of uint8 is 0 to 255";
        } else {
            $v = Types::toByte($value);
            if ($endianess & Endian::BIG_ENDIAN) {
                $v = "\x00" . $v;
            } else {
                $v = $v . "\x00";
            }
        }
        break;
    case 'int8':
        $value = (int)$value;
        if ($value < -128 || $value > 127) {
            $error = "valid range of int8 is -128 to 127";
        } else {
            $v = Types::toByte($value);
        }
        if ($endianess & Endian::BIG_ENDIAN) {
            $v = "\x00" . $v;
        } else {
            $v = $v . "\x00";
        }
        break;
    case 'uint16':
        $value = (int)$value;
        if ($value < 0 || $value > 65535) {
            $error = "valid range of uint16 is 0 to 65535";
        } else {
            $v = Types::toUint16($value);
        }
        break;
    case 'int16':
        $value = (int)$value;
        if ($value < -32768 || $value > 32767) {
            $error = "valid range of uint16 is -32768 to 32767";
        } else {
            $v = Types::toInt16($value);
        }
        break;
    case 'uint32':
        $value = (int)$value;
        if ($value < 0 || $value > 4294967295) {
            $error = "valid range of uint32 is 0 to 4294967295";
        } else {
            $v = Types::toUint32($value);
        }
        break;
    case 'int32':
        $value = (int)$value;
        if ($value < -2147483648 || $value > 2147483647) {
            $error = "valid range of int32 is -2147483648 to 2147483647";
        } else {
            $v = Types::toInt32($value);
        }
        break;
    case 'float32':
        $value = (float)$value;
        if ($value < -3.4e+38 || $value > 3.4e+38) {
            $error = "valid range of float32 is -3.4e+38 to 3.4e+38";
        } else {
            $v = Types::toReal($value);
        }
        break;
    case 'uint64':
        $value = (int)$value;
        if ($value < 0 || $value > 9223372036854775807) {
            $error = "valid range of uint64 is 0 to 9223372036854775807";
        } else {
            $v = Types::toUint64($value);
        }
        break;
    case 'int64':
        $value = (int)$value;
        if ($value < 0 || $value > 9223372036854775807) {
            $error = "valid range of int64 is -9223372036854775808 to 9223372036854775807";
        } else {
            $v = Types::toInt64($value);
        }
        break;
    case 'float64':
        $value = (double)$value;
        if ($value < 1.7E-308 || $value > 1.7E+308) {
            $error = "valid range of float64 is 1.7E-308 to 1.7E+308";
        } else {
            $v = Types::toDouble($value);
        }
        break;
    default:
        $error = "invalid data type";
}

$log = [];
$startTime = round(microtime(true) * 1000, 3);
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $v !== null) {
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
        $builder->setUri($deviceURI)
            ->setProtocol('serial')
            // delay this is crucial for some serial devices and delay needs to be long as 100ms (depending on the quantity)
            // or you will experience read errors ("stream_select interrupted") or invalid CRCs
            ->setDelayRead(100_000); // 100 milliseconds
    } else {
        $builder->setPort($port)->setHost($ip);
    }

    $connection = $builder->build();
    $packet = new WriteMultipleRegistersRequest($startAddress, [$v], $unitId);
    if ($isRTU) {
        $packet = RtuConverter::toRtu($packet);
        $log[] = 'Modbus RTU Packet to be sent (in hex): ' . unpack('H*', $packet)[1];
    } else {
        $log[] = 'Modbus TCP Packet to be sent (in hex): ' . $packet->toHex();
    }
    try {
        $binaryData = $connection->connect()->sendAndReceive($packet);

        $log[] = 'Binary received (in hex):   ' . unpack('H*', $binaryData)[1];
    } catch (Exception $exception) {
        $result = null;
        $log[] = 'An exception occurred';
        $log[] = $exception->getMessage();
        $log[] = $exception->getTraceAsString();
    } finally {
        $connection->close();
    }
}
$elapsed = round(microtime(true) * 1000) - $startTime;

?>
<h2>Example Modbus TCP/RTU Write Multiple Registers (FC=16)</h2>
<form method="post">
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

    <br>
    Endianess: <select name="endianess" id="endianess">
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
    Data type: <select name="type" id="data_type">
        <option value="uint8" <?php if ($dataType === 'uint8') {
            echo 'selected';
        } ?>>uint8/byte/char (1 Register, 1 byte)
        </option>
        <option value="int8" <?php if ($dataType === 'int8') {
            echo 'selected';
        } ?>>int8 (1 Register, 1 byte)
        </option>
        <option value="uint16" <?php if ($dataType === 'uint16') {
            echo 'selected';
        } ?>>uint16 (1 Register, 2 bytes)
        </option>
        <option value="uint16" <?php if ($dataType === 'uint16') {
            echo 'selected';
        } ?>>int16 (1 Register, 2 bytes)
        </option>
        <option value="uint32" <?php if ($dataType === 'uint32') {
            echo 'selected';
        } ?>>uint32 (2 Registers, 4 bytes)
        </option>
        <option value="int32" <?php if ($dataType === 'int32') {
            echo 'selected';
        } ?>>int32 (2 Registers, 4 bytes)
        </option>
        <option value="float32" <?php if ($dataType === 'float32') {
            echo 'selected';
        } ?>>float32/float/real (2 Registers, 4 bytes)
        </option>
        <option value="uint64" <?php if ($dataType === 'uint64') {
            echo 'selected';
        } ?>>uint64 (4 Registers, 8 bytes)
        </option>
        <option value="int64" <?php if ($dataType === 'int64') {
            echo 'selected';
        } ?>>int64 (4 Registers, 8 bytes)
        </option>
        <option value="float64" <?php if ($dataType === 'float64') {
            echo 'selected';
        } ?>>float64/double (4 Registers, 8 bytes)
        </option>
    </select><br>
    Value: <input name="value" type="number" step="any" id="value" value="<?php echo $value; ?>">
    <?php if ($v !== null) {
        echo "(as hex: <input name=\"hex\" id=\"hex\" value=\"" . bin2hex($v) . "\" disabled>)<br>";
    } ?>

    <br>
    <button type="submit">Send</button>
</form>
<?php if ($error) {
    echo $error;
}
?>
<h2>Debug info</h2>
<pre>
<?php
foreach ($log as $m) {
    echo $m . PHP_EOL;
}
?>
</pre>
Time <?php echo $elapsed ?> ms
</br>
Page generated: <?php echo date('c') ?>
