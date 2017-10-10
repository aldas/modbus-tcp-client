<?php
namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;

/**
 * Request for Read Input Discretes (FC=02)
 */
class ReadInputDiscretesRequest extends ReadCoilsRequest
{
    public function getFunctionCode()
    {
        return ModbusPacket::READ_INPUT_DISCRETES;
    }
}