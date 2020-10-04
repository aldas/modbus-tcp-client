<?php

namespace ModbusTcpClient\Utils;


use ModbusTcpClient\Network\IOException;

final class Packet
{
    private function __construct()
    {
        // no access, this is an utility class
    }

    /**
     * isCompleteLength checks if binary string is complete modbus packet
     * NB: this function works only for MODBUS TCP packets
     *
     * @param $binaryData string|null binary string to be checked
     * @return bool true if data is actual error packet
     */
    public static function isCompleteLength($binaryData): bool
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

}
