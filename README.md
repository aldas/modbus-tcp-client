# Modbus TCP protocol client

* Modbus TCP/IP description: http://www.simplymodbus.ca/TCP.htm

## Intention
This library is influenced by [phpmodbus](https://github.com/adduc/phpmodbus) library and meant to be provide decoupled Modbus protocol (packets) and networking related features so you could build modbus client with our own choice of networking code (ext_sockets/streams/Reactphp asynchronous streams) or use library provided networking classes (php Streams)