<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;

/**
 * Request for Read Input Discretes (FC=02)
 */
class ReadInputDiscretesRequest extends ReadCoilsRequest
{
    public function getFunctionCode(): int
    {
        return ModbusPacket::READ_INPUT_DISCRETES;
    }
}