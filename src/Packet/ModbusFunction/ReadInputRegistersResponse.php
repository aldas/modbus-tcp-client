<?php
namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\IModbusPacket;

/**
 * Request for Read Input Registers (FC=04)
 */
class ReadInputRegistersResponse extends ReadHoldingRegistersResponse
{
    public function getFunctionCode()
    {
        return IModbusPacket::READ_INPUT_REGISTERS;
    }
}