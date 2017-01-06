# Modbus TCP protocol client

* Modbus TCP/IP description: http://www.simplymodbus.ca/TCP.htm

##Supported functions

* FC1 - Read Coils
* FC2 - Read Input Discretes
* FC3 - Read Holding Registers
* FC4 - Read Input Registers
* FC5 - Write Single Coil
* FC6 - Write Single Register
* FC15 - Write Multiple Coils
* FC16 - Write Multiple Registers

## Intention
This library is influenced by [phpmodbus](https://github.com/adduc/phpmodbus) library and meant to be provide decoupled Modbus protocol (packets) and networking related features so you could build modbus client with our own choice of networking code (ext_sockets/streams/Reactphp asynchronous streams) or use library provided networking classes (php Streams)

## Example (fc3 - read holding registers)

```php
$connection = ModbusConnection::getBuilder()
    ->setHost('192.168.0.1')
    ->build();
    
$startAddress = 12288;
$quantity = 6;
$packet = new ReadHoldingRegistersRequest($startAddress, $quantity);

try {
    $binaryData = $connection->connect()
        ->send($packet)
        ->receive();

    //parse binary data to response object
    $response = ResponseFactory::parseResponse($binaryData);
    
    echo 'Data parsed from packet (bytes):' . PHP_EOL;
    print_r($response->getData());

} catch (Exception $exception) {
    echo $exception->getMessage() . PHP_EOL;
} finally {
    $connection->close();
}
```