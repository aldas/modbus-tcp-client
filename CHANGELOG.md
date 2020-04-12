# Changelog
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
