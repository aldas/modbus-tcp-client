<?php

namespace ModbusTcpClient\Packet;


use ModbusTcpClient\Utils\Types;

abstract class StartAddressResponse extends ProtocolDataUnit implements ModbusResponse
{
    /**
     * @var int
     */
    private $startAddress;

    public function __construct(string $rawData, int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($unitId, $transactionId);
        $this->startAddress = Types::parseUInt16(substr($rawData, 0, 2));
    }

    public function __toString(): string
    {
        return b''
            . $this->getHeader()->__toString()
            . Types::toByte($this->getFunctionCode())
            . Types::toRegister($this->startAddress);
    }

    /**
     * @return int
     */
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
        // do not use argument as this kind of packet gets start address from data
        return clone $this;
    }

    protected function getLengthInternal(): int
    {
        return 3; // 1 for fc + 2 for startAddress bytes
    }

}