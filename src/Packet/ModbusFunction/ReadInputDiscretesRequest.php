<?php
namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\IModbusPacket;

/**
 * Request for Read Input Discretes (FC=02)
 */
class ReadInputDiscretesRequest extends ReadCoilsRequest
{
    public function getFunctionCode()
    {
        return IModbusPacket::READ_INPUT_DISCRETES;
    }
}