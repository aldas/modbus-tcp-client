<?php
declare(strict_types=1);

namespace ModbusTcpClient\Utils;


use ModbusTcpClient\Network\IOException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusPacket;

final class Packet
{
    private function __construct()
    {
        // no access, this is an utility class
    }

    /**
     * isCompleteLength checks if binary string is complete modbus TCP packet
     * NB: this function works only for MODBUS TCP packets (request/response)
     *
     * @param string|null $binaryData binary string to be checked
     * @return bool true if data is actual modbus TCP packet
     */
    public static function isCompleteLength(string|null $binaryData): bool
    {
        // minimal amount is 8 bytes (header + function code)
        $length = strlen($binaryData);
        if ($length < 8) {
            return false;
        }
        // modbus header 6 bytes are = transaction id + protocol id + length of PDU part.
        // so adding these number is what complete packet would be
        $expectedLength = 6 + unpack('n', ($binaryData[4] . $binaryData[5]))[1];

        if ($length > $expectedLength) {
            return false;
        }
        return $length === $expectedLength;
    }

    /**
     * isCompleteLengthRTU checks if binary string is complete modbus RTU response packet
     * NB: this function works only for MODBUS RTU response packets
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
        $functionCode = ord($binaryData[1]);
        if (($functionCode & ErrorResponse::EXCEPTION_BITMASK) > 0) { // seems to be error response
            return true;
        }
        switch ($functionCode) {
            case ModbusPacket::READ_COILS: //
            case ModbusPacket::READ_INPUT_DISCRETES: //
            case ModbusPacket::READ_HOLDING_REGISTERS: //
            case ModbusPacket::READ_INPUT_REGISTERS: //
            case ModbusPacket::READ_WRITE_MULTIPLE_REGISTERS: //
                // if it is not error response then 3rd byte contains data length in bytes

                // trailing 3 bytes are = unit id (1) + function code (1) + data length in bytes (1) + (N)
                // next is N bytes of data that should match 3rd byte value
                $responseBytesLen = 3 + ord($binaryData[2]);
                break;
            case ModbusPacket::WRITE_SINGLE_COIL: // unit id (1) + function code (1) + start address (2) + coil data (2)
            case ModbusPacket::WRITE_SINGLE_REGISTER: // unit id (1) + function code (1) + start address (2) + register data (2)
            case ModbusPacket::WRITE_MULTIPLE_COILS: // unit id (1) + function code (1) + start address (2) + count of coils written (2)
            case ModbusPacket::WRITE_MULTIPLE_REGISTERS: // unit id (1) + function code (1) + start address (2) + count of registers written (2)
                $responseBytesLen = 6;
                break;
            case ModbusPacket::MASK_WRITE_REGISTER:
                $responseBytesLen = 8; // unit id (1) + function code (1) + start address (2) + AND mask (2) + OR mask (2)
                break;
            case ModbusPacket::GET_COMM_EVENT_COUNTER: // unit id (1) + function code (1) + status (2) + count (2)
                $responseBytesLen = 6;
                break;
            default:
                throw new IOException('can not determine complete length for unsupported modbus function code');
        }

        $expectedLength = $responseBytesLen + 2; // and 2 bytes for CRC
        if ($length > $expectedLength) {
            throw new IOException('packet length more bytes than expected');
        }
        return $length === $expectedLength;
    }

}
