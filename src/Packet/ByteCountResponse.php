<?php

namespace ModbusTcpClient\Packet;


use ModbusTcpClient\Utils\Types;

abstract class ByteCountResponse extends ProtocolDataUnit
{
    /**
     * @var int
     */
    private $byteCount;

    public function __construct($rawData, $unitId = 0, $transactionId = null)
    {
        $this->byteCount = Types::parseByte($rawData[0]);
        parent::__construct($unitId, $transactionId);
    }

    public function __toString()
    {
        return b''
            . $this->getHeader()
            . Types::toByte($this->getFunctionCode())
            . Types::toByte($this->byteCount);
    }

    /**
     * @return int
     */
    public function getByteCount()
    {
        return $this->byteCount;
    }

    protected function getLengthInternal()
    {
        return 2; // 1 for function code + 1 for byte count
    }

}