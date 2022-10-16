<?php
declare(strict_types=1);

namespace ModbusTcpClient\Utils;


use ModbusTcpClient\Network\IOException;
use ModbusTcpClient\Packet\ErrorResponse;

final class Packet
{
    private function __construct()
    {
        // no access, this is an utility class
    }

    /**
     * isCompleteLength checks if binary string is complete modbus TCP packet
     * NB: this function works only for MODBUS TCP packets
     *
     * @param string|null $binaryData binary string to be checked
     * @return bool true if data is actual modbus TCP packet
     */
    public static function isCompleteLength(string|null $binaryData): bool
    {
        // minimal amount is 9 bytes (header + function code + 1 byte of something ala error code)
        $length = strlen($binaryData);
        if ($length < 9) {
            return false;
        }
        // modbus header 6 bytes are = transaction id + protocol id + length of PDU part.
        // so adding these number is what complete packet would be
        $expectedLength = 6 + unpack('n', ($binaryData[4] . $binaryData[5]))[1];

        if ($length > $expectedLength) {
            throw new IOException('packet length more bytes than expected');
        }
        return $length === $expectedLength;
    }

    /**
     * isCompleteLengthRTU checks if binary string is complete modbus RTU packet
     * NB: this function works only for MODBUS RTU packets
     *
     * @param string|null $binaryData binary string to be checked
     * @return bool true if data is actual error packet
     */
    public static function isCompleteLengthRTU(string|null $binaryData): bool
    {
        // minimal RTU packet length is 5 bytes (1 byte unit id + 1 byte function code + 1 byte of error code or byte length + 2 bytes for CRC)
        $length = strlen($binaryData);
        if ($length < 5) {
            return false;
        }
        if ((ord($binaryData[1]) & ErrorResponse::EXCEPTION_BITMASK) > 0) { // seems to be error response
            return true;
        }
        // if it is not error response then 3rd byte contains data length in bytes

        // trailing 3 bytes are = unit id + function code + data length in bytes
        // next is N bytes of data that should match 3rd byte value
        // and 2 bytes for CRC
        // so adding these number is what complete packet would be
        $expectedLength = 3 + ord($binaryData[2]) + 2;

        if ($length > $expectedLength) {
            throw new IOException('packet length more bytes than expected');
        }
        return $length === $expectedLength;
    }

}
