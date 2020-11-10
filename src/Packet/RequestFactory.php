<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet;


use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputDiscretesRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadWriteMultipleRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleCoilsRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleCoilRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleRegisterRequest;

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
            case ModbusPacket::READ_INPUT_REGISTERS:
                return ReadInputRegistersRequest::parse($binaryString);
            case ModbusPacket::READ_COILS:
                return ReadCoilsRequest::parse($binaryString);
            case ModbusPacket::READ_INPUT_DISCRETES:
                return ReadInputDiscretesRequest::parse($binaryString);
            case ModbusPacket::WRITE_SINGLE_COIL:
                return WriteSingleCoilRequest::parse($binaryString);
            case ModbusPacket::WRITE_SINGLE_REGISTER:
                return WriteSingleRegisterRequest::parse($binaryString);
            case ModbusPacket::WRITE_MULTIPLE_COILS:
                return WriteMultipleCoilsRequest::parse($binaryString);
            case ModbusPacket::WRITE_MULTIPLE_REGISTERS:
                return WriteMultipleRegistersRequest::parse($binaryString);
            case ModbusPacket::READ_WRITE_MULTIPLE_REGISTERS:
                return ReadWriteMultipleRegistersRequest::parse($binaryString);
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
