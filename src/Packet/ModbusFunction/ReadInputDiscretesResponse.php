<?php
namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;

/**
 * Response for Read Input Discretes (FC=02)
 */
class ReadInputDiscretesResponse extends ReadCoilsResponse
{
    public function getFunctionCode()
    {
        return ModbusPacket::READ_INPUT_DISCRETES;
    }
}