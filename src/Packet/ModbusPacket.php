<?php

declare(strict_types=1);

namespace ModbusTcpClient\Packet;

interface ModbusPacket
{
    public const READ_COILS = 1; // 0x01
    public const READ_INPUT_DISCRETES = 2; // 0x02
    public const READ_HOLDING_REGISTERS = 3; // 0x03
    public const READ_INPUT_REGISTERS = 4; // 0x04
    public const WRITE_SINGLE_COIL = 5; // 0x05
    public const WRITE_SINGLE_REGISTER = 6; // 0x06
    public const GET_COMM_EVENT_COUNTER = 11; // 0x0B
    public const WRITE_MULTIPLE_COILS = 15; // 0x0F
    public const WRITE_MULTIPLE_REGISTERS = 16; // 0x10
    public const REPORT_SERVER_ID = 17; // 0x11
    public const MASK_WRITE_REGISTER = 22; // 0x16
    public const READ_WRITE_MULTIPLE_REGISTERS = 23; // 0x17


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
