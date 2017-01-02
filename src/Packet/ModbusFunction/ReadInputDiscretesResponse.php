<?php
namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Packet\ProtocolDataUnitResponse;

/**
 * Response for Read Input Discretes (FC=02)
 */
class ReadInputDiscretesResponse extends ProtocolDataUnitResponse
{

    public function getFunctionCode()
    {
        return IModbusPacket::READ_INPUT_DISCRETES;
    }

    public static function parse($binaryString)
    {
        // TODO: Implement parse() method.
    }
}