<?php


namespace ModbusTcpClient\Packet;


use ModbusTcpClient\ModbusException;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Utils\Types;

class ResponseFactory
{
    /**
     * @param $binaryString
     * @return IModbusPacket
     */
    public static function parseResponse($binaryString)
    {
        if ($binaryString === null || strlen($binaryString) < 9) { // 7 bytes for MBAP header and at least 2 bytes for PDU
            throw new ModbusException('Response null or data length too short to be valid packet!');
        }

        $functionCode = ord($binaryString[7]);

        if (($functionCode & ErrorResponse::EXCEPTION_BITMASK) > 0) {
            $functionCode -= ErrorResponse::EXCEPTION_BITMASK; //function code is in low bits of exception
            $exceptionCode = Types::parseByte($binaryString[8]);

            //TODO throw an exception already here?
            return new ErrorResponse(ModbusApplicationHeader::parse($binaryString), $functionCode, $exceptionCode);
        }

        $transactionId = Types::parseUInt16BE($binaryString[0] . $binaryString[1]);
        $unitId = Types::parseByte($binaryString[6]);

        $rawData = substr($binaryString, 8);

        //TODO add all response types
        //TODO should responses parse all their data themselves?
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

    public static function parseResponseOrThrow($binaryString) {
        $response = static::parseResponse($binaryString);
        if ($response instanceof ErrorResponse) {
            throw new ModbusException($response->getErrorMessage(), $response->getErrorCode());
        }
        return $response;
    }

}