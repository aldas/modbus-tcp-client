<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet;


use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;

class RequestFactory
{
    /**
     * @param string|null $binaryString
     * @return ModbusRequest|ErrorResponse
     * @throws \ModbusTcpClient\Exception\ModbusException
     */
    public static function parseRequest($binaryString)
    {
        if ($binaryString === null || strlen($binaryString) < 9) { // 7 bytes for MBAP header and at least 2 bytes for PDU
            throw new ModbusException('Request null or data length too short to be valid packet!');
        }

        $functionCode = ord($binaryString[7]);
        if (($functionCode & ErrorResponse::EXCEPTION_BITMASK) > 0) {
            // respond with 'illegal function'
            return new ErrorResponse(ModbusApplicationHeader::parse($binaryString), $functionCode, 1);
        }

        switch ($functionCode) {
            case ModbusPacket::READ_HOLDING_REGISTERS:
                return ReadHoldingRegistersRequest::parse($binaryString);
                break;
//            case ModbusPacket::READ_INPUT_REGISTERS:
//                return new ReadInputRegistersResponse($rawData, $unitId, $transactionId);
//                break;
//            case ModbusPacket::READ_COILS:
//                return new ReadCoilsResponse($rawData, $unitId, $transactionId);
//                break;
//            case ModbusPacket::READ_INPUT_DISCRETES:
//                return new ReadInputDiscretesResponse($rawData, $unitId, $transactionId);
//                break;
//            case ModbusPacket::WRITE_SINGLE_COIL:
//                return new WriteSingleCoilResponse($rawData, $unitId, $transactionId);
//                break;
//            case ModbusPacket::WRITE_SINGLE_REGISTER:
//                return new WriteSingleRegisterResponse($rawData, $unitId, $transactionId);
//                break;
//            case ModbusPacket::WRITE_MULTIPLE_COILS:
//                return new WriteMultipleCoilsResponse($rawData, $unitId, $transactionId);
//                break;
//            case ModbusPacket::WRITE_MULTIPLE_REGISTERS:
//                return new WriteMultipleRegistersResponse($rawData, $unitId, $transactionId);
//                break;
//            case ModbusPacket::READ_WRITE_MULTIPLE_REGISTERS:
//                return new ReadWriteMultipleRegistersResponse($rawData, $unitId, $transactionId);
//                break;
            default:
                return new ErrorResponse(ModbusApplicationHeader::parse($binaryString), $functionCode, 1);

        }
    }

    public static function parseRequestOrThrow($binaryString): ModbusRequest
    {
        $response = static::parseRequest($binaryString);
        if ($response instanceof ErrorResponse) {
            throw new ModbusException($response->getErrorMessage(), $response->getErrorCode());
        }
        return $response;
    }

}
