<?php

namespace ModbusTcpClient\Packet;


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
     */
    private $unitId = 0;

    public function __construct($length, $unitId = 0, $transactionId = null)
    {
        $this->validate($length, $unitId, $transactionId);

        $this->length = $length + 1; // + 1 is for unitId size
        $this->unitId = $unitId;
        $this->transactionId = $transactionId ?: mt_rand(1, Types::MAX_VALUE_UINT16);
    }

    /**
     * @return int
     */
    public function getTransactionId()
    {
        return $this->transactionId;
    }

    /**
     * @return int
     */
    public function getProtocolId()
    {
        return self::PROTOCOL_ID;
    }

    /**
     * @return int
     */
    public function getLength()
    {
        return $this->length;
    }

    /**
     * @return int
     */
    public function getUnitId()
    {
        return $this->unitId;
    }

    public function __toString()
    {
        return b''
            . Types::toUInt16BE($this->getTransactionId())
            . Types::toUInt16BE($this->getProtocolId())
            . Types::toUInt16BE($this->getLength())
            . Types::toByte($this->getUnitId());
    }

    public static function parse($binaryString)
    {
//        $data = unpack();
    }

    private function validate($length, $unitId, $transactionId)
    {
        if ((null === $length) || !($length > 0 && $length <= Types::MAX_VALUE_UINT16)) {
            throw new \OutOfRangeException("length is not set or out of range: {$this->length}");
        }
        if (!($unitId >= 0 && $unitId <= Types::MAX_VALUE_BYTE)) {
            throw new \OutOfRangeException("unitId is out of range: {$this->unitId}");
        }
        if ((null !== $transactionId) && !($transactionId >= 0 && $transactionId <= Types::MAX_VALUE_UINT16)) {
            throw new \OutOfRangeException("transactionId is out of range: {$this->transactionId}");
        }
    }
}
