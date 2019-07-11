<?php

namespace ModbusTcpClient\Packet;


use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Utils\Types;

class ModbusApplicationHeader
{
    /**
     * @var int 2 bytes set by the Client, always = 00 00
     */
    const PROTOCOL_ID = 0;

    /**
     * @var int 2 bytes set by the Client to uniquely identify each request. These bytes are echoed by the Server since its responses may not be received in the same order as the requests.
     */
    private $transactionId;

    /**
     * @var int 2 bytes identifying the number of bytes in the message (PDU = ProtocolDataUnit) to follow (function data size + 1 byte for unitId size)
     */
    private $length;

    /**
     * @var int 1 byte set by the Client and echoed by the Server for identification of a remote slave connected on a serial line or on other buses
     * also known as SlaveID
     */
    private $unitId = 0;

    public function __construct($length, $unitId = 0, $transactionId = null)
    {
        $this->validate($length, $unitId, $transactionId);

        $this->length = $length + 1; // + 1 is for unitId size
        $this->unitId = $unitId;
        $this->transactionId = $transactionId ?: random_int(1, Types::MAX_VALUE_UINT16);
    }

    /**
     * @return int
     */
    public function getTransactionId(): int
    {
        return $this->transactionId;
    }

    /**
     * @return int
     */
    public function getProtocolId(): int
    {
        return self::PROTOCOL_ID;
    }

    /**
     * @return int
     */
    public function getLength(): int
    {
        return $this->length;
    }

    /**
     * Returns unit id / slave Id value
     * @return int
     */
    public function getUnitId(): int
    {
        return $this->unitId;
    }

    public function __toString(): string
    {
        return b''
            . Types::toRegister($this->getTransactionId())
            . Types::toRegister($this->getProtocolId())
            . Types::toRegister($this->getLength())
            . Types::toByte($this->getUnitId());
    }

    public static function parse($binaryString): ModbusApplicationHeader
    {

        if (strlen($binaryString) < 7) {
            throw new ModbusException('Data length too short to be valid header!');
        }

        $transactionId = Types::parseUInt16($binaryString[0] . $binaryString[1]);
        $length = Types::parseUInt16($binaryString[4] . $binaryString[5]);
        $unitId = Types::parseByte($binaryString[6]);

        return new ModbusApplicationHeader(
            $length,
            $unitId,
            $transactionId
        );
    }

    private function validate($length, $unitId, $transactionId)
    {
        if (!$length || !($length > 0 && $length <= Types::MAX_VALUE_UINT16)) {
            throw new InvalidArgumentException("length is not set or out of range (uint16): {$length}");
        }
        if (!($unitId >= 0 && $unitId <= 247)) {
            throw new InvalidArgumentException("unitId is out of range (0-247): {$unitId}");
        }
        if ((null !== $transactionId) && !($transactionId >= 0 && $transactionId <= Types::MAX_VALUE_UINT16)) {
            throw new InvalidArgumentException("transactionId is out of range (uint16): {$transactionId}");
        }
    }
}
