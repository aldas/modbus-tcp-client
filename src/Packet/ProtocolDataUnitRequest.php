<?php

namespace ModbusTcpClient\Packet;


/*
 * Here is an example of a Modbus RTU request for the content of analog output holding registers # 40108 to 40110.
 * 03 006B 0003
 *
 * 03: The Function Code (read Analog Output Holding Registers)
 * 006B: The Data Address of the first register requested. (40108-40001 = 107 =6B hex)
 * 0003: The total number of registers requested. (read 3 registers 40108 to 40110)
 */

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Utils\Types;

abstract class ProtocolDataUnitRequest extends ProtocolDataUnit
{
    private $startAddress;

    public function __construct(int $startAddress, int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($unitId, $transactionId);

        $this->startAddress = $startAddress;
    }

    public function __toString(): string
    {
        return b''
            . $this->getHeader()->__toString()
            . Types::toByte($this->getFunctionCode())
            . Types::toUint16($this->getStartAddress());
    }

    public function getStartAddress(): int
    {
        return $this->startAddress;
    }

    protected function getLengthInternal(): int
    {
        return 3; // size of function code (1 byte) + startAddress (2 bytes)
    }

    protected function validate()
    {
        if ((null === $this->startAddress) || !($this->startAddress >= 0 && $this->startAddress <= Types::MAX_VALUE_UINT16)) {
            throw new InvalidArgumentException("startAddress is not set or out of range: {$this->startAddress}");
        }
    }

}