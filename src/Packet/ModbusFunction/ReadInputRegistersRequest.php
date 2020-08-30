<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;

/**
 * Request for Read Input Registers (FC=04)
 *
 * Example packet: \x00\x01\x00\x00\x00\x06\x01\x04\x00\x6B\x00\x01
 * \x00\x01 - transaction id
 * \x00\x00 - protocol id
 * \x00\x06 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x01 - unit id
 * \x04 - function code
 * \x00\x6B - start address
 * \x00\x01 - input registers quantity to return
 *
 */
class ReadInputRegistersRequest extends ReadHoldingRegistersRequest
{
    public function getFunctionCode(): int
    {
        return ModbusPacket::READ_INPUT_REGISTERS;
    }
}
