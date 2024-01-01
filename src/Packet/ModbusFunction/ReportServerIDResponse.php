<?php

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusResponse;
use ModbusTcpClient\Packet\ProtocolDataUnit;
use ModbusTcpClient\Utils\Types;

/**
 * Response for Report Server ID (FC=17, 0x11)
 *
 * Example packet: \x81\x80\x00\x00\x00\x08\x10\x11\x02\x01\x02\x00\x01\x02
 * \x81\x80 - transaction id
 * \x00\x00 - protocol id
 * \x00\x08 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x10 - unit id
 * \x11 - function code
 * \x02 - byte count for server id
 * \x01\x02 - N bytes for server id (device specific, variable length)
 * \x00 - run status
 * \x01\x02 - optional N bytes for additional data (device specific, variable length)
 *
 */
class ReportServerIDResponse extends ProtocolDataUnit implements ModbusResponse
{
    /**
     * @var string server ID bytes as binary string
     */
    private string $serverID;

    /**
     * @var int run status
     */
    private int $status;

    /**
     * @var string|null additional data (optional)
     */
    private ?string $additionalData = null;

    public function __construct(string $rawData, int $unitId = 0, int $transactionId = null)
    {
        $serverIDLength = Types::parseByte($rawData[0]);
        if (strlen($rawData) < ($serverIDLength + 2)) {
            throw new InvalidArgumentException("too few bytes to be a complete report server id packet");
        }
        $this->serverID = substr($rawData, 1, $serverIDLength);
        $this->status = Types::parseByte($rawData[$serverIDLength + 1]);
        if (strlen($rawData) > ($serverIDLength + 2)) {
            $this->additionalData = substr($rawData, $serverIDLength + 2);
        }

        parent::__construct($unitId, $transactionId);

    }

    public function getStatus(): int
    {
        return $this->status;
    }

    /**
     * Server ID value as binary string
     * @return string
     */
    public function getServerID(): string
    {
        return $this->serverID;
    }

    /**
     * @return int[]
     */
    public function getServerIDBytes(): array
    {
        return Types::parseByteArray($this->serverID);
    }

    public function getAdditionalData(): ?string
    {
        return $this->additionalData;
    }

    /**
     * @return int[]
     */
    public function getAdditionalDataBytes(): array
    {
        if ($this->additionalData) {
            return Types::parseByteArray($this->additionalData);
        }
        return [];
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::REPORT_SERVER_ID; // 17 (0x11)
    }

    public function __toString(): string
    {
        $serverID = $this->getServerID();
        $additionalData = $this->getAdditionalData();

        $result = b''
            . $this->getHeader()->__toString()
            . Types::toByte($this->getFunctionCode())
            . Types::toByte(strlen($serverID))
            . $serverID
            . Types::toByte($this->getStatus());
        if ($additionalData !== null) {
            $result .= $additionalData;
        }
        return $result;
    }

    protected function getLengthInternal(): int
    {
        $serverIDLength = strlen($this->getServerID());
        $additionalDataLength = strlen($this->getAdditionalData());
        // size of function code (1 byte) +
        // server id byte count (1 byte) +
        // server id value bytes (N bytes) +
        // status (1 byte) +
        // additional data bytes (N bytes)
        return 3 + $serverIDLength + $additionalDataLength;
    }

    public function withStartAddress(int $startAddress): static
    {
        // Note: I am being stupid and stubborn here. Somehow `ModbusResponse` interface ended up having this method
        // and want this response to work with ResponseFactory::parseResponse method.
        // TODO: change ModbusResponse interface or ResponseFactory::parseResponse signature
        return clone $this;
    }
}