<?php

namespace ModbusTcpClient\Packet;


use ModbusTcpClient\Utils\Types;

abstract class StartAddressResponse extends ProtocolDataUnit
{
    /**
     * @var int
     */
    private $startAddress;

    public function __construct($rawData, $unitId = 0, $transactionId = null)
    {
        parent::__construct($unitId, $transactionId);
        $this->startAddress = Types::parseUInt16BE(substr($rawData, 0, 2));
    }

    public function __toString()
    {
        return b''
            . $this->getHeader()
            . Types::toByte($this->getFunctionCode())
            . Types::toUInt16BE($this->startAddress);
    }

    /**
     * @return int
     */
    public function getStartAddress()
    {
        return $this->startAddress;
    }

    protected function getLengthInternal()
    {
        return 2;
    }

}