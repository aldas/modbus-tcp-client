<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet;


interface ModbusPacket
{
    const READ_COILS = 1; // 0x01
    const READ_INPUT_DISCRETES = 2; // 0x02
    const READ_HOLDING_REGISTERS = 3; // 0x03
    const READ_INPUT_REGISTERS = 4; // 0x04
    const WRITE_SINGLE_COIL = 5; // 0x05
    const WRITE_SINGLE_REGISTER = 6; // 0x06
    const GET_COMM_EVENT_COUNTER = 11; // 0x0B
    const WRITE_MULTIPLE_COILS = 15; // 0x0F
    const WRITE_MULTIPLE_REGISTERS = 16; // 0x10
    const REPORT_SERVER_ID = 17; // 0x11
    const MASK_WRITE_REGISTER = 22; // 0x16
    const READ_WRITE_MULTIPLE_REGISTERS = 23; // 0x17


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
