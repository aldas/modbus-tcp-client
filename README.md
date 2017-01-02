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