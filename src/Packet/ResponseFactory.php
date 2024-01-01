<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet;


use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Exception\ParseException;
use ModbusTcpClient\Packet\ModbusFunction\GetCommEventCounterResponse;
use ModbusTcpClient\Packet\ModbusFunction\MaskWriteRegisterResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputDiscretesResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadWriteMultipleRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReportServerIDResponse;
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
            case ModbusPacket::READ_HOLDING_REGISTERS: // 3 (0x03)
                return new ReadHoldingRegistersResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::READ_INPUT_REGISTERS: // 4 (0x04)
                return new ReadInputRegistersResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::READ_COILS: // 1 (0x01)
                return new ReadCoilsResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::READ_INPUT_DISCRETES: // 2 (0x02)
                return new ReadInputDiscretesResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::WRITE_SINGLE_COIL: // 5 (0x05)
                return new WriteSingleCoilResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::WRITE_SINGLE_REGISTER: // 6 (0x06)
                return new WriteSingleRegisterResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::GET_COMM_EVENT_COUNTER: // 11 (0x0B)
                return new GetCommEventCounterResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::WRITE_MULTIPLE_COILS: // 15 (0x0F)
                return new WriteMultipleCoilsResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::WRITE_MULTIPLE_REGISTERS: // 16 (0x10)
                return new WriteMultipleRegistersResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::REPORT_SERVER_ID: // 17 (0x11)
                return new ReportServerIDResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::MASK_WRITE_REGISTER: // 22 (0x16)
                return new MaskWriteRegisterResponse($rawData, $unitId, $transactionId);
            case ModbusPacket::READ_WRITE_MULTIPLE_REGISTERS: // 23 (0x17)
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
