# Modbus TCP protocol client
[![Build Status](https://travis-ci.org/aldas/modbus-tcp-client.svg?branch=master)](https://travis-ci.org/aldas/modbus-tcp-client)

* Modbus TCP/IP specification: http://www.modbus.org/specs.php
* Modbus TCP/IP simpler description: http://www.simplymodbus.ca/TCP.htm

## Supported functions

* FC1 - Read Coils ([ReadCoilsRequest](src/Packet/ModbusFunction/ReadCoilsRequest.php) / [ReadCoilsResponse](src/Packet/ModbusFunction/ReadCoilsResponse.php))
* FC2 - Read Input Discretes ([ReadInputDiscretesRequest](src/Packet/ModbusFunction/ReadInputDiscretesRequest.php) / [ReadInputDiscretesResponse](src/Packet/ModbusFunction/ReadInputDiscretesResponse.php))
* FC3 - Read Holding Registers ([ReadHoldingRegistersRequest](src/Packet/ModbusFunction/ReadHoldingRegistersRequest.php) / [ReadHoldingRegistersResponse](src/Packet/ModbusFunction/ReadHoldingRegistersResponse.php))
* FC4 - Read Input Registers ([ReadInputRegistersRequest](src/Packet/ModbusFunction/ReadInputRegistersRequest.php) / [ReadInputRegistersResponse](src/Packet/ModbusFunction/ReadInputRegistersResponse.php))
* FC5 - Write Single Coil ([WriteSingleCoilRequest](src/Packet/ModbusFunction/WriteSingleCoilRequest.php) / [WriteSingleCoilResponse](src/Packet/ModbusFunction/WriteSingleCoilResponse.php))
* FC6 - Write Single Register ([WriteSingleRegisterRequest](src/Packet/ModbusFunction/WriteSingleRegisterRequest.php) / [WriteSingleRegisterResponse](src/Packet/ModbusFunction/WriteSingleRegisterResponse.php))
* FC15 - Write Multiple Coils ([WriteMultipleCoilsRequest](src/Packet/ModbusFunction/WriteMultipleCoilsRequest.php) / [WriteMultipleCoilsResponse](src/Packet/ModbusFunction/WriteMultipleCoilsResponse.php))
* FC16 - Write Multiple Registers ([WriteMultipleRegistersRequest](src/Packet/ModbusFunction/WriteMultipleRegistersRequest.php) / [WriteMultipleRegistersResponse](src/Packet/ModbusFunction/WriteMultipleRegistersResponse.php))

## Requirements

* PHP 5.6+ (64bit PHP! 32bit php does not support 64bit ints and overflows with 32bit unsigned integers when 32th bit is set)

## Intention
This library is influenced by [phpmodbus](https://github.com/adduc/phpmodbus) library and meant to be provide decoupled Modbus protocol (request/response packets) and networking related features so you could build modbus client with our own choice of networking code (ext_sockets/streams/Reactphp asynchronous streams) or use library provided networking classes (php Streams)

## Endianness
Library supports following byte and word orders:
* Big endian (ABCD)
* Big endian low word first (CDAB) (used by Wago-750)
* Little endian (DCBA)
* Little endian low word first (BADC)

See [Endian.php](src/Utils/Endian.php) for additional info and [Types.php](src/Utils/Types.php) for supported data types.

## Example (fc3 - read holding registers)

Some of the Modbus function examples are in `examples/` folder

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

## Try comminication with PLCs quickly with php built-in web server

Examples folder has [index.php](examples/index.php) which can be used with php built-in web server to test
out communication with our own PLCs.

```
git clone https://github.com/aldas/modbus-tcp-client.git
cd modbus-tcp-client
composer install
php -S localhost:8080 -t examples/
```

Now open <http://localhost:8080> in browser. See additional query parameters from [index.php](examples/index.php).


## Tests

* all `composer test`
* unit tests `composer test-unit`
* integration tests `composer test-integration`

For Windows users:
* all ` vendor/bin/phpunit`
* unit tests ` vendor/bin/phpunit --testsuite 'unit-tests'`
* integration tests ` vendor/bin/phpunit --testsuite 'integration-tests'`
