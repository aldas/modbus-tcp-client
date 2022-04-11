<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet;


interface ModbusResponse extends ModbusPacket
{
    public function withStartAddress(int $startAddress): static;
}
