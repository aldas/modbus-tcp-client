<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet;


use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Exception\ParseException;
use ModbusTcpClient\Packet\ModbusFunction\MaskWriteRegisterResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputDiscretesResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadWriteMultipleRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleCoilsResponse;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleCoilResponse;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleRegisterResponse;
use ModbusTcpClient\Utils\Endian;
use ModbusTcpClient\Utils\Types;

class ResponseFactory
{
    /**
     * @param string|null $binaryString
     * @return ModbusResponse|ErrorResponse
     * @throws ModbusException
     */
    public static function parseResponse(string|null $binaryString): ModbusResponse|ErrorResponse
    {
        if ($binaryString === null || strlen($binaryString) < 9) { // 7 bytes for MBAP header and at least 2 bytes for PDU
            throw new ModbusException('Response null or data length too short to be valid packet!');
        }

        $functionCode = ord($binaryString[7]);

        if (($functionCode & ErrorResponse::EXCEPTION_BITMASK) > 0) {
            $functionCode -= ErrorResponse::EXCEPTION_BITMASK; //function code is in low bits of exception
            $exceptionCode = Types::parseByte($binaryString[8]);

            return new ErrorResponse(ModbusApplicationHeader::parse($binaryString), $functionCode, $exceptionCode);
        }

        $transactionId = Types::parseUInt16($binaryString[0] . $binaryString[1], Endian::BIG_ENDIAN);
        $unitId = Types::parseByte($binaryString[6]);

        $rawData = substr($binaryString, 8);

        switch ($functionCode) {
            case ModbusPacket::READ_HOLDING_REGISTERS:
                return new ReadHoldingRegistersResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::READ_INPUT_REGISTERS:
                return new ReadInputRegistersResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::READ_COILS:
                return new ReadCoilsResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::READ_INPUT_DISCRETES:
                return new ReadInputDiscretesResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::WRITE_SINGLE_COIL:
                return new WriteSingleCoilResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::WRITE_SINGLE_REGISTER:
                return new WriteSingleRegisterResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::WRITE_MULTIPLE_COILS:
                return new WriteMultipleCoilsResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::WRITE_MULTIPLE_REGISTERS:
                return new WriteMultipleRegistersResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::MASK_WRITE_REGISTER:
                return new MaskWriteRegisterResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::READ_WRITE_MULTIPLE_REGISTERS:
                return new ReadWriteMultipleRegistersResponse($rawData, $unitId, $transactionId);
            default:
                throw new ParseException("Unknown function code '{$functionCode}' read from response packet");

        }
    }

    /**
     * @param string|null $binaryString
     * @return ModbusResponse
     * @throws ModbusException
     */
    public static function parseResponseOrThrow(string|null $binaryString): ModbusResponse
    {
        $response = static::parseResponse($binaryString);
        if ($response instanceof ErrorResponse) {
            throw new ModbusException($response->getErrorMessage(), $response->getErrorCode());
        }
        return $response;
    }

}
