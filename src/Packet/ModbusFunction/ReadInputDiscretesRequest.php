<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;

/**
 * Request for Read Input Discretes (FC=02)
 *
 * Example packet: \x81\x80\x00\x00\x00\x06\x10\x02\x00\x6B\x00\x03
 * \x81\x80 - transaction id
 * \x00\x00 - protocol id
 * \x00\x06 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x10 - unit id
 * \x02 - function code
 * \x00\x6B - start address
 * \x00\x03 - input discretes quantity to return
 *
 */
class ReadInputDiscretesRequest extends ReadCoilsRequest
{
    public function getFunctionCode(): int
    {
        return ModbusPacket::READ_INPUT_DISCRETES;
    }
}
