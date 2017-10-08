<?php

namespace ModbusTcpClient\Packet;


use ModbusTcpClient\Utils\Types;

abstract class ByteCountResponse extends ProtocolDataUnit
{
    /** @var int */
    private $byteCount;

    /** @var int */
    private $startAddress = 0;

    /**
     * @var int $addressStep step size between two words in sequence. For Wago PLCs it is 1 as words are addressed %QW256, %QW257 etc \
     *          but this does not mean that some other plc could address by bytes - if so just set value to 2 as 1 word = 2 bytes
     */
    private $addressStep = 1;

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

    public function getStartAddress()
    {
        return $this->startAddress;
    }

    public function getAddressStep()
    {
        return $this->addressStep;
    }

    /**
     * @param int $startAddress
     * @param int $addressStep
     * @return static
     */
    public function withStartAddress($startAddress, $addressStep = 1)
    {
        $new = clone $this;
        $new->startAddress = $startAddress;
        $new->addressStep = $addressStep;

        return $new;
    }

}