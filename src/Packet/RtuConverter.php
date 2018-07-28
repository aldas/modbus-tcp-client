<?php

namespace ModbusTcpClient\Packet;


use ModbusTcpClient\Exception\ParseException;
use ModbusTcpClient\Utils\Types;

/**
 * Converts Modbus TCP/IP packet to/from Modbus RTU packet.
 *
 * Read differences between Modbus RTU and Modbus TCP/IP packet http://www.simplymodbus.ca/TCP.htm
 *
 * Difference is:
 * 1. RTU header contains only slave id. TCP/IP header contains of transaction id, protocol id, length, unitid
 * 2. RTU packed has CRC16 appended
 *
 * NB: RTU slave id equivalent is TCP/IP packet unit id
 */
final class RtuConverter
{
    private function __construct()
    {
        // utility class
    }

    /**
     * Convert Modbus TCP request instance to Modbus RTU binary packet
     *
     * @param ModbusRequest $request request to be converted
     * @return string Modbus RTU request in binary form
     */
    public static function toRtu(ModbusRequest $request): string
    {
        // trim 6 bytes: 2 bytes for transaction id + 2 bytes for protocol id + 2 bytes for data length field
        $packet = substr((string)$request, 6);
        return $packet . self::crc16($packet);
    }

    /**
     * Converts binary string containing RTU response packet to Modbus TCP response instance
     *
     * @param string $binaryData rtu binary response
     * @param array $options option to use during conversion
     * @return ModbusResponse converted Modbus TCP packet
     * @throws \ModbusTcpClient\Exception\ParseException
     * @throws \Exception if it was not possible to gather sufficient entropy
     */
    public static function fromRtu(string $binaryData, array $options = []): ModbusResponse
    {
        $data = substr($binaryData, 0, -2); // remove and crc

        if ((bool)($options['no_crc_check'] ?? false) === false) {
            $originalCrc = substr($binaryData, -2);
            $calculatedCrc = self::crc16($data);
            if ($originalCrc !== $calculatedCrc) {
                throw new ParseException(
                    sprintf('Packet crc (\x%s) does not match calculated crc (\x%s)!',
                        bin2hex($originalCrc),
                        bin2hex($calculatedCrc)
                    )
                );
            }
        }

        $packet = b''
            . Types::toRegister(random_int(0, Types::MAX_VALUE_UINT16)) // 2 bytes for transaction id
            . "\x00\x00" // 2 bytes for protocol id
            . Types::toRegister(strlen($data)) // 2 bytes for data length field
            . $data;

        return ResponseFactory::parseResponse($packet);
    }

    private static function crc16(string $string): string
    {
        $crc = 0xFFFF;
        for ($x = 0, $xMax = \strlen($string); $x < $xMax; $x++) {
            $crc ^= \ord($string[$x]);
            for ($y = 0; $y < 8; $y++) {
                if (($crc & 0x0001) === 0x0001) {
                    $crc = (($crc >> 1) ^ 0xA001);
                } else {
                    $crc >>= 1;
                }
            }
        }

        return \chr($crc & 0xFF) . \chr($crc >> 8);
    }
}