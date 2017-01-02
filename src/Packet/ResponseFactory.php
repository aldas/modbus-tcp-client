<?php


namespace ModbusTcpClient\Packet;


use ModbusTcpClient\ModbusException;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Utils\Types;

class ResponseFactory
{
    public static function parseResponse($binaryString)
    {
        if (strlen($binaryString) < 9) { // 7 bytes for MBAP header and at least 2 bytes for PDU
            throw new ModbusException('Response data length too short to be valid packet!');
        }

        $functionCode = ord($binaryString[7]);

        if (($functionCode & ExceptionResponse::EXCEPTION_BITMASK) > 0) {
            $functionCode -= ExceptionResponse::EXCEPTION_BITMASK; //function code is in low bits of exception
            $exceptionCode = Types::parseByte($binaryString[8]);

            return new ExceptionResponse(ModbusApplicationHeader::parse($binaryString), $functionCode, $exceptionCode);
        }


        $transactionId = Types::parseUInt16BE($binaryString[0] . $binaryString[1]);
        $length = Types::parseUInt16BE($binaryString[4] . $binaryString[5]);
        $unitId = Types::parseByte($binaryString[6]);

        $rawData = substr($binaryString, 9);

        switch ($functionCode) {
            case IModbusPacket::READ_HOLDING_REGISTERS:
                return new ReadHoldingRegistersResponse($rawData, $unitId, $transactionId);
                break;
            case IModbusPacket::READ_COILS:
                return new ReadCoilsResponse($rawData, $unitId, $transactionId);
                break;
            default:
                throw new \InvalidArgumentException("Unknown function code '{$functionCode}' read from response packet");

        }
    }

}