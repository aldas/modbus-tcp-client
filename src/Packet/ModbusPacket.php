<?php

namespace ModbusTcpClient\Packet;


interface ModbusPacket
{
    const READ_COILS = 1;
    const READ_INPUT_DISCRETES = 2;
    const READ_HOLDING_REGISTERS = 3;
    const READ_INPUT_REGISTERS = 4;
    const WRITE_SINGLE_COIL = 5;
    const WRITE_SINGLE_REGISTER = 6;
    const WRITE_MULTIPLE_COILS = 15;
    const WRITE_MULTIPLE_REGISTERS = 16;
    const READ_WRITE_MULTIPLE_REGISTERS = 23;


    /**
     * @return ModbusApplicationHeader
     */
    public function getHeader(): ModbusApplicationHeader;

    /**
     * @return int
     */
    public function getFunctionCode(): int;

    /**
     * @return string
     */
    public function __toString(): string;

    /**
     * @return string
     */
    public function toHex(): string;
}
