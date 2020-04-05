<?php

use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersResponse;
use ModbusTcpClient\Packet\RtuConverter;

require __DIR__ . '/../vendor/autoload.php';

if (stripos(PHP_OS, 'WIN') === 0) {
    echo 'This example can not be run in Windows!' . PHP_EOL;
    exit(0);
}

$startAddress = 1;
$quantity = 2;
$slaveId = 1; // RTU packet slave id equivalent is Modbus TCP unitId

$tcpPacket = new ReadInputRegistersRequest($startAddress, $quantity, $slaveId);
$rtuPacket = RtuConverter::toRtu($tcpPacket);

try {
    $device = '/dev/ttyUSB0';
    $fd = fopen($device, 'w+b');

    $sttyModes = implode(' ', [
        'cs8', // enable character size 8 bits
        '9600', // enable baud rate 9600
        '-icanon', // disable enable special characters: erase, kill, werase, rprnt
        'min 0', // with -icanon, set N characters minimum for a completed read
        'ignbrk', // enable ignore break characters
        '-brkint', // disable breaks cause an interrupt signal
        '-icrnl', // disable translate carriage return to newline
        '-imaxbel', // disable beep and do not flush a full input buffer on a character
        '-opost', // disable postprocess output
        '-onlcr', // disable translate newline to carriage return-newline
        '-isig', // disable interrupt, quit, and suspend special characters
        '-iexten', // disable non-POSIX special characters
        '-echo', // disable echo input characters
        '-echoe', // disable echo erase characters as backspace-space-backspace
        '-echok', // disable echo a newline after a kill character
        '-echoctl', // disable same as [-]ctlecho
        '-echoke', // disable kill all line by obeying the echoprt and echoe settings
        '-noflsh', // disable flushing after interrupt and quit special characters
        '-ixon', // disable XON/XOFF flow control
        '-crtscts', // disable RTS/CTS handshaking
    ]);
    $sttyResult = exec("stty -F ${device} ${sttyModes}");
    if ($sttyResult === false) {
        echo 'stty command failed' . PHP_EOL;
        exit(1);
    }

    echo 'RTU Binary to sent (in hex):   ' . unpack('H*', $rtuPacket)[1] . PHP_EOL;
    fwrite($fd, $rtuPacket);
    fflush($fd);

    $binaryData = '';
    $start = microtime(true);

    do {
        // give sensor (5ms) some time to respond. SHT20 modbus minimal response time seems to be 20ms and more
        usleep(5000);
        $binaryData = fread($fd, 255);
    } while ($binaryData === '');

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
    fclose($fd);
}
