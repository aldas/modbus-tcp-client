<?php
namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\IModbusPacket;

/**
 * Response for Read Input Discretes (FC=02)
 */
class ReadInputDiscretesResponse extends ReadCoilsResponse
{
    public function getFunctionCode()
    {
        return IModbusPacket::READ_INPUT_DISCRETES;
    }
}