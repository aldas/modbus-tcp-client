<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet;


use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Packet\ModbusFunction\GetCommEventCounterRequest;
use ModbusTcpClient\Packet\ModbusFunction\MaskWriteRegisterRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputDiscretesRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadWriteMultipleRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReportServerIDRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleCoilsRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleCoilRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleRegisterRequest;

class RequestFactory
{
    /**
     * @param string|null $binaryString
     * @return ModbusRequest|ErrorResponse
     * @throws ModbusException
     */
    public static function parseRequest(string|null $binaryString): ModbusRequest|ErrorResponse
    {
        if ($binaryString === null || strlen($binaryString) < 8) { // 7 bytes for MBAP header and at least 1 bytes for PDU
            throw new ModbusException('Request null or data length too short to be valid packet!');
        }

        $functionCode = ord($binaryString[7]);
        if (($functionCode & ErrorResponse::EXCEPTION_BITMASK) > 0) {
            // respond with 'illegal function'
            return new ErrorResponse(ModbusApplicationHeader::parse($binaryString), $functionCode, 1);
        }

        switch ($functionCode) {
            case ModbusPacket::READ_HOLDING_REGISTERS: // 3 (0x03)
                return ReadHoldingRegistersRequest::parse($binaryString);
            case ModbusPacket::READ_INPUT_REGISTERS: // 4 (0x04)
                return ReadInputRegistersRequest::parse($binaryString);
            case ModbusPacket::READ_COILS: // 1 (0x01)
                return ReadCoilsRequest::parse($binaryString);
            case ModbusPacket::READ_INPUT_DISCRETES: // 2 (0x02)
                return ReadInputDiscretesRequest::parse($binaryString);
            case ModbusPacket::WRITE_SINGLE_COIL: // 5 (0x05)
                return WriteSingleCoilRequest::parse($binaryString);
            case ModbusPacket::WRITE_SINGLE_REGISTER: // 6 (0x06)
                return WriteSingleRegisterRequest::parse($binaryString);
            case ModbusPacket::GET_COMM_EVENT_COUNTER: // 11 (0x0B)
                return GetCommEventCounterRequest::parse($binaryString);
            case ModbusPacket::WRITE_MULTIPLE_COILS: // 15 (0x0F)
                return WriteMultipleCoilsRequest::parse($binaryString);
            case ModbusPacket::WRITE_MULTIPLE_REGISTERS: // 16 (0x10)
                return WriteMultipleRegistersRequest::parse($binaryString);
            case ModbusPacket::REPORT_SERVER_ID: // 17 (0x11)
                return ReportServerIDRequest::parse($binaryString);
            case ModbusPacket::MASK_WRITE_REGISTER: // 22 (0x16)
                return MaskWriteRegisterRequest::parse($binaryString);
            case ModbusPacket::READ_WRITE_MULTIPLE_REGISTERS: // 23 (0x17)
                return ReadWriteMultipleRegistersRequest::parse($binaryString);
            default:
                return new ErrorResponse(ModbusApplicationHeader::parse($binaryString), $functionCode, 1);

        }
    }

    /**
     * @param string|null $binaryString
     * @return ModbusRequest
     * @throws ModbusException
     */
    public static function parseRequestOrThrow(string|null $binaryString): ModbusRequest
    {
        $response = static::parseRequest($binaryString);
        if ($response instanceof ErrorResponse) {
            throw new ModbusException($response->getErrorMessage(), $response->getErrorCode());
        }
        return $response;
    }

}
