<?php

namespace ModbusTcpClient\Packet;


use ModbusTcpClient\Exception\ParseException;
use ModbusTcpClient\Utils\Types;

abstract class ByteCountResponse extends ProtocolDataUnit implements ModbusResponse
{
    /** @var int */
    private $byteCount;

    /** @var int */
    private $startAddress = 0;

    public function __construct(string $rawData, int $unitId = 0, int $transactionId = null)
    {
        $this->byteCount = Types::parseByte($rawData[0]);

        $bytesInPacket = (strlen($rawData) - 1);
        if ($this->byteCount !== $bytesInPacket) {
            throw new ParseException("packet byte count does not match bytes in packet! count: {$this->byteCount}, actual: {$bytesInPacket}");
        }

        parent::__construct($unitId, $transactionId);
    }

    public function __toString(): string
    {
        return b''
            . $this->getHeader()
            . Types::toByte($this->getFunctionCode())
            . Types::toByte($this->byteCount);
    }

    /**
     * @return int
     */
    public function getByteCount(): int
    {
        return $this->byteCount;
    }

    protected function getLengthInternal(): int
    {
        return 2; // 1 for function code + 1 for byte count
    }

    public function getStartAddress(): int
    {
        return $this->startAddress;
    }

    /**
     * @param int $startAddress
     * @param int $addressStep
     * @return static
     */
    public function withStartAddress(int $startAddress)
    {
        $new = clone $this;
        $new->startAddress = $startAddress;

        return $new;
    }
}