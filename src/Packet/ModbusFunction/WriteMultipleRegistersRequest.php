<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;


use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusRequest;
use ModbusTcpClient\Packet\ProtocolDataUnitRequest;
use ModbusTcpClient\Utils\Registers;
use ModbusTcpClient\Utils\Types;

/**
 * Request for Write Multiple Registers (FC=16)
 */
class WriteMultipleRegistersRequest extends ProtocolDataUnitRequest implements ModbusRequest
{
    /**
     * @var array registers (array of bytes)
     */
    private $registers;
    private $registersCount;
    private $registersBytesSize;

    public function __construct(int $startAddress, array $registers, int $unitId = 0, int $transactionId = null)
    {
        $this->registers = $registers;
        $this->registersBytesSize = Registers::getRegisterArrayByteSize($this->registers);
        $this->registersCount = $this->registersBytesSize / 2;

        parent::__construct($startAddress, $unitId, $transactionId);

        $this->validate();
    }

    public function validate()
    {
        parent::validate();

        if ($this->registersCount === 0 || $this->registersCount > 124) {
            // as request contain 1 byte field 'registersBytesSize' to indicate number of bytes to follow
            // there is no way more than 124 words (124*2 bytes) can be written as this field would overflow
            throw new InvalidArgumentException("registers count out of range (1-124): {$this->registersCount}");
        }
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::WRITE_MULTIPLE_REGISTERS;
    }

    public function __toString(): string
    {
        return parent::__toString()
            . Types::toRegister($this->registersCount)
            . Types::toByte($this->registersBytesSize)
            . Registers::getRegisterArrayAsByteString($this->registers);
    }

    /**
     * @return array
     */
    public function getRegisters(): array
    {
        return $this->registers;
    }

    protected function getLengthInternal(): int
    {
        // (function code size (1) + startAddress size (2)) + registers count size (2) + register byte size (1) + number of bytes registers need for data
        return parent::getLengthInternal() + (3 + $this->registersBytesSize);
    }
}