# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [2.4] - 2021-12-26

### Added

* support for `double` (64bit double precision floating point) data type to API. This requires PHP 7.2+ as it uses 
  https://www.php.net/manual/en/function.pack.php `E` and `e` formats.

## [2.3.1] - 2021-12-05

### Added

* Use PHP 8.1 in CI flow.
* `examples/index.php` now supports FC3 and FC4 requests.

### Fixed

* From PHP 8.1 `Types::parseAsciiStringFromRegister()` fails to convert extended ASCII (8bit) characters to UTF-8. Introduced 
   `Charset::$defaultCharset` to be able to set default charset used to convert strings.

## [2.3.0] - 2021-05-09

### Added

* Allow high level API setting custom endian for `string` address with `ReadRegistersBuilder` and `StringReadRegisterAddress` classes.

## [2.2.0] - 2021-05-04

### Added

* Allow high level API setting custom endian for each address with `ReadRegistersBuilder` and `ReadRegisterAddress` classes.

## [2.1.1] - 2021-01-26

### Fixed

* `BinaryStreamConnection` endless loop (CPU @ 100%) when stream is ready for reading but stream returns no data at all (#73)

### Added

* Example for Modbus server [examples/example_response_server.php](examples/example_response_server.php)

### Changed

* Changed examples to use `Packet::isCompleteLength()`

## [2.1.0] - 2020-10-04

### Added

* Added functions to check is received data is complete/error Modbus TCP packet (PR #63)
    * [Packet::isCompleteLength](src/Utils/Packet.php)
    * [ErrorResponse::is](src/Packet/ErrorResponse.php)

### Changed

* Changed `StreamHandler` to read data until complete packet is received. Previously we read only once from stream
    and naively assumed that Modbus TCP packet can not be fragmented over multiple TCP packets/ multiple stream reads.
    This fixed #61.

## [2.0.1] - 2020-04-12

### Security

* Escape stty command for SerialStreamCreator to avoid unescaped arguments (#54)

## [2.0.0] - 2020-04-07

### Added

* Added high level API to compose Coil (fc1/2) requests. See:
    * `ModbusTcpClient\Composer\Read\ReadCoilsBuilder` 
    * `ModbusTcpClient\Composer\Write\WriteCoilsBuilder` 
* Added 'serial' protocol support to `BinaryStreamConnection`. See [examples/rtu_usb_to_serial_stream.php](examples/rtu_usb_to_serial_stream.php) how to use it.
* Added example to read modbus RTU from Usb to rs-485 adapter and SHT20 sensor (#50)
* Adds Function 23 (0x17) Read/Write Multiple registers support (#47)
* Started changelog

### Changed

* Adds request time on example request page
* (BREAKING change) abstract class `ModbusTcpClient\Composer\Address` changed to interface
