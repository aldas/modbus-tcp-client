# Modbus TCP and RTU over TCP protocol client

[![Latest Version](https://img.shields.io/packagist/v/aldas/modbus-tcp-client.svg)](https://packagist.org/packages/aldas/modbus-tcp-client)
[![Packagist](https://img.shields.io/packagist/dm/aldas/modbus-tcp-client.svg)](https://packagist.org/packages/aldas/modbus-tcp-client)
[![Software License](https://img.shields.io/packagist/l/aldas/modbus-tcp-client.svg)](LICENSE)
[![codecov](https://codecov.io/gh/aldas/modbus-tcp-client/branch/master/graph/badge.svg)](https://codecov.io/gh/aldas/modbus-tcp-client)

* Modbus TCP/IP specification: http://www.modbus.org/specs.php
* Modbus TCP/IP and RTU simpler description: http://www.simplymodbus.ca/TCP.htm
## Installation

Use [Composer](https://getcomposer.org/) to install this library as dependency.
```bash
composer require aldas/modbus-tcp-client
```

## Supported functions

* FC1 - Read Coils ([ReadCoilsRequest](src/Packet/ModbusFunction/ReadCoilsRequest.php) / [ReadCoilsResponse](src/Packet/ModbusFunction/ReadCoilsResponse.php))
* FC2 - Read Input Discretes ([ReadInputDiscretesRequest](src/Packet/ModbusFunction/ReadInputDiscretesRequest.php) / [ReadInputDiscretesResponse](src/Packet/ModbusFunction/ReadInputDiscretesResponse.php))
* FC3 - Read Holding Registers ([ReadHoldingRegistersRequest](src/Packet/ModbusFunction/ReadHoldingRegistersRequest.php) / [ReadHoldingRegistersResponse](src/Packet/ModbusFunction/ReadHoldingRegistersResponse.php))
* FC4 - Read Input Registers ([ReadInputRegistersRequest](src/Packet/ModbusFunction/ReadInputRegistersRequest.php) / [ReadInputRegistersResponse](src/Packet/ModbusFunction/ReadInputRegistersResponse.php))
* FC5 - Write Single Coil ([WriteSingleCoilRequest](src/Packet/ModbusFunction/WriteSingleCoilRequest.php) / [WriteSingleCoilResponse](src/Packet/ModbusFunction/WriteSingleCoilResponse.php))
* FC6 - Write Single Register ([WriteSingleRegisterRequest](src/Packet/ModbusFunction/WriteSingleRegisterRequest.php) / [WriteSingleRegisterResponse](src/Packet/ModbusFunction/WriteSingleRegisterResponse.php))
* FC11 - Get Communication Event Counter ([GetCommEventCounterRequest](src/Packet/ModbusFunction/GetCommEventCounterRequest.php) / [GetCommEventCounterResponse](src/Packet/ModbusFunction/GetCommEventCounterResponse.php))
* FC15 - Write Multiple Coils ([WriteMultipleCoilsRequest](src/Packet/ModbusFunction/WriteMultipleCoilsRequest.php) / [WriteMultipleCoilsResponse](src/Packet/ModbusFunction/WriteMultipleCoilsResponse.php))
* FC16 - Write Multiple Registers ([WriteMultipleRegistersRequest](src/Packet/ModbusFunction/WriteMultipleRegistersRequest.php) / [WriteMultipleRegistersResponse](src/Packet/ModbusFunction/WriteMultipleRegistersResponse.php))
* FC17 - Report Server ID ([ReportServerIDRequest](src/Packet/ModbusFunction/ReportServerIDRequest.php) / [ReportServerIDResponse](src/Packet/ModbusFunction/ReportServerIDResponse.php))
* FC22 - Mask Write Register ([MaskWriteRegisterRequest](src/Packet/ModbusFunction/MaskWriteRegisterRequest.php) / [MaskWriteRegisterResponse](src/Packet/ModbusFunction/MaskWriteRegisterResponse.php))
* FC23 - Read / Write Multiple Registers ([ReadWriteMultipleRegistersRequest](src/Packet/ModbusFunction/ReadWriteMultipleRegistersRequest.php) / [ReadWriteMultipleRegistersResponse](src/Packet/ModbusFunction/ReadWriteMultipleRegistersResponse.php))

### Utility functions

* [Packet::isCompleteLength](src/Utils/Packet.php) - checks if data is complete Modbus TCP request or response packet
* [Packet::isCompleteLengthRTU()](src/Utils/Packet.php) - checks if data is complete Modbus RTU response packet
* [ErrorResponse::is](src/Packet/ErrorResponse.php) - checks if data is Modbus TCP error packet

## Requirements

* PHP 8.0+
* Release [2.4.0](https://github.com/aldas/modbus-tcp-client/tree/2.4.0) was last to support PHP 7 (7.4 might work with v3.0.0)
* Release [0.2.0](https://github.com/aldas/modbus-tcp-client/tree/0.2.0) was last to support PHP 5.6

## Intention
This library is influenced by [phpmodbus](https://github.com/adduc/phpmodbus) library and meant to be provide decoupled Modbus protocol (request/response packets) and networking related features so you could build modbus client with our own choice of networking code (ext_sockets/streams/Reactphp/Amp asynchronous streams) or use library provided networking classes (php Streams)

## Endianness
Applies to multibyte data that are stored in Word/Double/Quad word registers basically everything
that is not (u)int16/byte/char. 

So if we receive from network 0x12345678 (bytes: ABCD) and want to convert that to a 32 bit register there could be 4 different 
ways to interpret bytes and word order depending on modbus server architecture and client architecture.
NB: TCP, and UDP, are transmitted in big-endian order so we choose this as base for examples

Library supports following byte and word orders:
* Big endian (ABCD - word1 = 0x1234, word2 = 0x5678) 
* Big endian low word first (CDAB - word1 = 0x5678, word2 = 0x1234) (used by Wago-750)
* Little endian (DCBA - word1 = 0x3412, word2 = 0x7856)
* Little endian low word first (BADC - word1 = 0x7856, word2 = 0x3412)

Default (global) endianess used for parsing can be changed with:
```php
Endian::$defaultEndian = Endian::BIG_ENDIAN_LOW_WORD_FIRST;
```

For non-global cases see API methods argument list if method support using custom endianess.

See [Endian.php](src/Utils/Endian.php) for additional info and [Types.php](src/Utils/Types.php) for supported data types.


## Data types

Modbus is binary protocol which revolves about addresses of Registers/Word (16bit, 2 byte of data) and Coils (1 bit of data).
Coils are booleans but Register/Word or multiple Registers can hold different data types. Following is ways to access different
data types from Registers:

Most of the for data types have optional arguments for providing Endian type. By default data is parsed as being Big Endian Low Word first endian.

Exaple: getting uint32 value as Little Endian Low Word First
```php
$dword->getUInt32(Endian::LITTLE_ENDIAN | Endian::LOW_WORD_FIRST);
```

### 1bit - 16bit data types

1-16bit data types are hold by `Word` class which hold 2 bytes of data.
```php
$address = 100;
$word = $response->getWordAt($address);
```

Following methods exists to get different types out of single `Word` instance:
* `boolean` - 1bit, true/false, `$word->isBitSet(11)`
* `byte` - 8bit, 1 byte, range 0 to 255
  * first byte of Word (0) `$word->getHighByteAsInt()`
  * last byte of Word (1) `$word->getLowByteAsInt()`
* `uint16` - 16bit, 2 byte, range 0 to 65535 `$word->getUInt16()`
* `int16` - 16bit, 2 byte, range -32768 to 32767 `$word->getInt16()`

and following additional methods:
* `$word->getBytes()` return Words as array of 2 integers (0-255)


### 32bit data types

17-32bit data types are hold by `DoubleWord` class which hold 4 bytes of data.
```php
$address = 100;
$dword = $response->getDoubleWordAt($address);
```

Following methods exists to get different types out of single `DoubleWord` instance:
* `uint32` - 32bit, 4 bytes, range 0 to 4294967295, `$dword->getUInt32()`
* `int32` - 64bit, 8 bytes, range -2147483648 to 2147483647, `$dword->getInt32()`
* `float` - 64bit, 8 bytes, range -3.4e+38 to 3.4e+38, `$dword->getFloat()`

and following additional methods:
* `$dword->getBytes()` return DoubleWord as array of 4 integers (0-255)
* `$dword->getHighBytesAsWord()` returns first 2 bytes as `Word`
* `$dword->getLowBytesAsWord()` returns last 2 bytes as `Word` 


### 64bit data types

64bit data types are hold by `QuadWord` class which hold 8 bytes of data.
NB: 64-bit PHP supports only up to 63-bit (signed) integers.
```php
$address = 100;
$qword = $response->getQuadWordAt($address);
```

Following methods exists to get different types out of single `QuadWord` instance:
* `uint32` - 64bit, 8 bytes, range 0 to 9223372036854775807, `$dword->getUInt64()`
* `int32` - 64bit, 8 bytes, range -9223372036854775808 to 9223372036854775807, `$dword->getInt64()`
* `double` - 64bit, 8 bytes, range 2.2250738585072e-308 to 1.7976931348623e+308, `$dword->getDouble()`

and following additional methods:
* `$qword->getBytes()` return `QuadWord` as array of 8 integers (0-255)
* `$qword->getHighBytesAsDoubleWord()` returns first 4 bytes as `DoubleWord`
* `$qword->getLowBytesAsDoubleWord()` returns last 4 bytes as `DoubleWord`


### Strings

ASCII (8bit character) string can be extracted from response as utf-8 string
```php
$address = 20;
$length = 10;
$string = $response->getAsciiStringAt($address, $length);
```


## Example of Modbus TCP (fc3 - read holding registers)

Some of the Modbus function examples are in [examples/](examples) folder

Advanced usage:
* command line poller with ReachPHP [examples/example_cli_poller.php](examples/example_cli_poller.php)
* send/recieve packets parallel using non-blocking IO:
  * using [ReactPHP](https://reactphp.org/) see 'examples/[example_parallel_requests_reactphp.php](examples/example_parallel_requests_reactphp.php)'
  * using [Amp](https://amphp.org/amp/) see 'examples/[example_parallel_requests_amp.php](examples/example_parallel_requests_amp.php)'

Request multiple packets with higher level API:
```php
$address = 'tcp://127.0.0.1:5022';
$unitID = 0; // also known as 'slave ID'
$fc3 = ReadRegistersBuilder::newReadHoldingRegisters($address, $unitID)
    ->unaddressableRanges([[100,110], [1512]])
    ->bit(256, 15, 'pump2_feedbackalarm_do')
    // will be split into 2 requests as 1 request can return only range of 124 registers max
    ->int16(657, 'battery3_voltage_wo')
    // will be another request as uri is different for subsequent int16 register
    ->useUri('tcp://127.0.0.1:5023')
    ->string(
        669,
        10,
        'username_plc2',
        function ($value, $address, $response) {
            return 'prefix_' . $value; // optional: transform value after extraction
        },
        function (\Exception $exception, Address $address, $response) {
            // optional: callback called then extraction failed with an error
            return $address->getType() === Address::TYPE_STRING ? '' : null; // does not make sense but gives you an idea
        }
    )
    ->build(); // returns array of 3 ReadHoldingRegistersRequest requests

// this will use PHP non-blocking stream io to recieve responses
$responseContainer = (new NonBlockingClient(['readTimeoutSec' => 0.2]))->sendRequests($fc3);
print_r($responseContainer->getData()); // array of assoc. arrays (keyed by address name)
print_r($responseContainer->getErrors());
```
Response structure
```php
[
    [ 'pump2_feedbackalarm_do' => true, ],
    [ 'battery3_voltage_wo' => 12, ],
    [ 'username_plc2' => 'prefix_admin', ]
]
```

Low level - send packets:
```php
$connection = BinaryStreamConnection::getBuilder()
    ->setHost('192.168.0.1')
    ->build();
    
$packet = new ReadHoldingRegistersRequest(256, 8); //create FC3 request packet

try {
    $binaryData = $connection->connect()->sendAndReceive($packet);

    //parse binary data to response object
    $response = ResponseFactory::parseResponseOrThrow($binaryData);
    
    //same as 'foreach ($response->getWords() as $word) {'
    foreach ($response as $word) { 
        print_r($word->getInt16());
    }
    // print registers as double words in big endian low word first order (as WAGO-750 does)
    foreach ($response->getDoubleWords() as $dword) {
        print_r($dword->getInt32(Endian::BIG_ENDIAN_LOW_WORD_FIRST));
    }
        
    // set internal index to match start address to simplify array access
    $responseWithStartAddress = $response->withStartAddress(256);
    print_r($responseWithStartAddress[256]->getBytes()); // use array access to get word
    print_r($responseWithStartAddress->getDoubleWordAt(257)->getFloat());
} catch (Exception $exception) {
    echo $exception->getMessage() . PHP_EOL;
} finally {
    $connection->close();
}
```

## Example of Modbus RTU over TCP
Difference between Modbus RTU and Modbus TCP is that:

1. RTU header contains only slave id. TCP/IP header contains of transaction id, protocol id, length, unitid
2. RTU packed has 2 byte CRC appended

See http://www.simplymodbus.ca/TCP.htm for more detailsed explanation

This library was/is originally meant for Modbus TCP but it has support to convert packet to RTU and from RTU. See this [examples/rtu.php](examples/rtu.php) for example.
```php
$rtuBinaryPacket = RtuConverter::toRtu(new ReadHoldingRegistersRequest($startAddress, $quantity, $slaveId));
$binaryData = $connection->connect()->sendAndReceive($rtuBinaryPacket);
$responseAsTcpPacket = RtuConverter::fromRtu($binaryData);
```

## Example of Modbus RTU over USB to Serial (RS485) adapter

See Linux example in 'examples/[rtu_usb_to_serial.php](examples/rtu_usb_to_serial.php)'


## Example of Modbus RTU over TCP + higher level API usage

See example in 'examples/[rtu_over_tcp_with_higherlevel_api.php](examples/rtu_over_tcp_with_higherlevel_api.php)'


## Example of non-blocking socket IO with ReactPHP/Amp (i.e. modbus request are run in 'parallel')

* 'examples/[example_parallel_requests_reactphp.php](examples/example_parallel_requests_reactphp.php) - example of non-blocking socket IO with ReactPHP socket library (https://github.com/reactphp/socket)
* 'examples/[example_parallel_requests_amp.php](examples/example_parallel_requests_amp.php) - example of non-blocking socket IO with Amp socket library https://github.com/amphp/socket 

## Example Modbus server (accepting requests) with ReactPHP

* 'examples/[example_response_server.php](examples/example_response_server.php) - example of modbus server 


## Try communication with PLCs quickly using php built-in web server

Examples folder has [index.php](examples/index.php) which can be used with php built-in web server to test
out communication with our own PLCs.

```
git clone https://github.com/aldas/modbus-tcp-client.git
cd modbus-tcp-client
composer install
php -S localhost:8080 -t examples/
```

Now open <http://localhost:8080> in browser. See additional query parameters from [index.php](examples/index.php).

## Changelog

See [CHANGELOG.md](CHANGELOG.md)

## Tests

* all `composer test`
* unit tests `composer test-unit`
* integration tests `composer test-integration`

For Windows users:
* all ` vendor/bin/phpunit`
* unit tests ` vendor/bin/phpunit --testsuite 'unit-tests'`
* integration tests ` vendor/bin/phpunit --testsuite 'integration-tests'`

# Static analysis

Run [PHPStan](https://phpstan.org/) analysis `compose check`
