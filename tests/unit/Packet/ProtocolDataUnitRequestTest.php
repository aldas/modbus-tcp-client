<?php

namespace Tests\Packet;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ProtocolDataUnitRequest;
use ModbusTcpClient\Utils\Types;
use PHPUnit\Framework\TestCase;

class ProtocolDataUnitRequestTest extends TestCase
{
    public function testValidation()
    {
        $instance = new ProtocolDataUnitRequestImpl(0, 0, 255);

        $this->assertEquals(0, $instance->getStartAddress());
        $this->assertEquals(23, $instance->getFunctionCode());
        $this->assertEquals(0, $instance->getHeader()->getUnitId());
        $this->assertEquals(255, $instance->getHeader()->getTransactionId());
    }

    public function testFailWithNegativeStartAddress()
    {
        $this->expectExceptionMessage("startAddress is out of range: -1");
        $this->expectException(InvalidArgumentException::class);

        new ProtocolDataUnitRequestImpl(-1);
    }

    public function testFailWithTooBigStartAddress()
    {
        $this->expectExceptionMessage("startAddress is out of range: 65536");
        $this->expectException(InvalidArgumentException::class);

        new ProtocolDataUnitRequestImpl(Types::MAX_VALUE_UINT16 + 1);
    }

}

class ProtocolDataUnitRequestImpl extends ProtocolDataUnitRequest
{
    public function __construct($startAddress, $unitId = 0, $transactionId = null)
    {
        parent::__construct($startAddress, $unitId, $transactionId);
        parent::validate();
    }

    public function getFunctionCode(): int
    {
        return 23;
    }
}
