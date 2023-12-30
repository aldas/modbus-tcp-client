<?php

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusRequest;
use ModbusTcpClient\Packet\ProtocolDataUnit;
use ModbusTcpClient\Utils\Types;

/**
 * Request for Get Communication Event Counter (FC=11, 0x0b)
 *
 * Example packet: \x81\x80\x00\x00\x00\x02\x10\x0b
 * \x81\x80 - transaction id
 * \x00\x00 - protocol id
 * \x00\x08 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x10 - unit id
 * \x0b - function code
 *
 */
class GetCommEventCounterRequest extends ProtocolDataUnit implements ModbusRequest
{
    public function __construct(int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($unitId, $transactionId);
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::GET_COMM_EVENT_COUNTER; // 11 (0x0b)
    }

    protected function getLengthInternal(): int
    {
        return 1; // size of function code (1 byte)
    }

    public function __toString(): string
    {
        return b''
            . $this->getHeader()->__toString()
            . Types::toByte($this->getFunctionCode());
    }

    /**
     * Parses binary string to GetCommEventCounterRequest or return ErrorResponse on failure
     *
     * @param string $binaryString
     * @return GetCommEventCounterRequest|ErrorResponse
     */
    public static function parse(string $binaryString): GetCommEventCounterRequest|ErrorResponse
    {
        return self::parsePacket(
            $binaryString,
            8,
            ModbusPacket::GET_COMM_EVENT_COUNTER,
            function (int $transactionId, int $unitId) {
                return new self($unitId, $transactionId);
            }
        );
    }
}