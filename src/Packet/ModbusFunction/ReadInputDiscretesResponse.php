<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;

/**
 * Response for Read Input Discretes (FC=02)
 *
 * Example packet: \x81\x80\x00\x00\x00\x05\x03\x02\x02\xCD\x6B
 * \x81\x80 - transaction id
 * \x00\x00 - protocol id
 * \x00\x05 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x03 - unit id
 * \x02 - function code
 * \x02 - coils byte count
 * \xCD\x6B - input discrete data (2 bytes = 2 * 8 inputs)
 *
 */
class ReadInputDiscretesResponse extends ReadCoilsResponse
{
    public function getFunctionCode(): int
    {
        return ModbusPacket::READ_INPUT_DISCRETES;
    }
}
