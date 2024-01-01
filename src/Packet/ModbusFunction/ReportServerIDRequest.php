<?php

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusRequest;
use ModbusTcpClient\Packet\ProtocolDataUnit;
use ModbusTcpClient\Utils\Types;

/**
 * Request for Report Server ID (FC=17, 0x11)
 *
 * Example packet: \x81\x80\x00\x00\x00\x02\x10\x11
 * \x81\x80 - transaction id
 * \x00\x00 - protocol id
 * \x00\x08 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x10 - unit id
 * \x11 - function code
 *
 */
class ReportServerIDRequest extends ProtocolDataUnit implements ModbusRequest
{
    public function __construct(int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($unitId, $transactionId);
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::REPORT_SERVER_ID; // 17 (0x11)
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
     * Parses binary string to ReportServerIDRequest or return ErrorResponse on failure
     *
     * @param string $binaryString
     * @return ReportServerIDRequest|ErrorResponse
     */
    public static function parse(string $binaryString): ReportServerIDRequest|ErrorResponse
    {
        return self::parsePacket(
            $binaryString,
            8,
            ModbusPacket::REPORT_SERVER_ID,
            function (int $transactionId, int $unitId) {
                return new self($unitId, $transactionId);
            }
        );
    }
}