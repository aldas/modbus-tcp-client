<?php

namespace ModbusTcpClient\Packet;


interface ModbusResponse extends ModbusPacket
{
    public function withStartAddress(int $startAddress);
}