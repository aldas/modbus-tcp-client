<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;

/**
 * Request for Read Input Registers (FC=04)
 */
class ReadInputRegistersRequest extends ReadHoldingRegistersRequest
{
    public function getFunctionCode(): int
    {
        return ModbusPacket::READ_INPUT_REGISTERS;
    }
}