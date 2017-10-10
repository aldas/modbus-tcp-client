<?php
namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;

/**
 * Request for Read Input Registers (FC=04)
 */
class ReadInputRegistersRequest extends ReadHoldingRegistersRequest
{
    public function getFunctionCode()
    {
        return ModbusPacket::READ_INPUT_REGISTERS;
    }
}