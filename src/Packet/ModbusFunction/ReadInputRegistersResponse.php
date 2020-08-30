<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;

/**
 * Request for Read Input Registers (FC=04)
 *
 * Example packet: \x81\x80\x00\x00\x00\x05\x01\x04\x02\xCD\x6B
 * \x81\x80 - transaction id
 * \x00\x00 - protocol id
 * \x00\x05 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x01 - unit id
 * \x04 - function code
 * \x02 - returned registers byte count
 * \xCD\x6B - input registers data (1 register)
 *
 */
class ReadInputRegistersResponse extends ReadHoldingRegistersResponse
{
    public function getFunctionCode(): int
    {
        return ModbusPacket::READ_INPUT_REGISTERS;
    }
}
