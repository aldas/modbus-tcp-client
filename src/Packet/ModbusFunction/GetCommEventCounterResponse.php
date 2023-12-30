<?php

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusResponse;
use ModbusTcpClient\Packet\ProtocolDataUnit;
use ModbusTcpClient\Utils\Endian;
use ModbusTcpClient\Utils\Types;

/**
 * Response for Get Communication Event Counter (FC=11, 0x0b)
 *
 * Example packet: \x81\x80\x00\x00\x00\x08\x10\x0b\xFF\xFF\x00\x01
 * \x81\x80 - transaction id
 * \x00\x00 - protocol id
 * \x00\x08 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x10 - unit id
 * \x0b - function code
 * \xFF\xFF - status (0xFFFF = previously request command is still being run. ok, 0x0000 = no running)
 * \x00\x01 - event counter value (0 to 65535)
 *
 */
class GetCommEventCounterResponse extends ProtocolDataUnit implements ModbusResponse
{
    /**
     * @var int function code of event counter being requested
     */
    private int $status;

    /**
     * @var int event counter value (0 to 65535)
     */
    private int $eventCount;

    public function __construct(string $rawData, int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($unitId, $transactionId);
        $this->status = Types::parseUInt16(substr($rawData, 0, 2), Endian::BIG_ENDIAN);
        $this->eventCount = Types::parseUInt16(substr($rawData, 2, 2), Endian::BIG_ENDIAN);
    }

    public function getStatus(): int
    {
        return $this->status;
    }

    public function getEventCount(): int
    {
        return $this->eventCount;
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::GET_COMM_EVENT_COUNTER; // 11 (0x0b)
    }

    public function __toString(): string
    {
        return b''
            . $this->getHeader()->__toString()
            . Types::toByte($this->getFunctionCode())
            . Types::toRegister($this->getStatus()) // 0xFFFF or 0x0000
            . Types::toUint16($this->getEventCount(), Endian::BIG_ENDIAN);
    }

    protected function getLengthInternal(): int
    {
        return 5; // size of function code (1 byte) + status (2 bytes) + event count (2 bytes)
    }

    public function withStartAddress(int $startAddress): static
    {
        // Note: I am being stupid and stubborn here. Somehow `ModbusResponse` interface ended up having this method
        // and want this response to work with ResponseFactory::parseResponse method.
        // TODO: change ModbusResponse interface or ResponseFactory::parseResponse signature
        return clone $this;
    }
}