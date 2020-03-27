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
 * Request for Read / Write Multiple Registers (FC=23)
 */
class ReadWriteMultipleRegistersRequest extends ProtocolDataUnitRequest implements ModbusRequest
{
    /** @var int $readQuantity quantity of registers to return in response */
    private $readQuantity;
    /** @var int $writeStartAddress start of address where data is written from request */
    private $writeStartAddress;

    /**
     * @var array registers (array of bytes)
     */
    private $writeRegisters;
    private $writeRegisterCount;
    private $writeRegistersBytesSize;

    public function __construct(
        int $readStartAddress,
        int $readQuantity,
        int $writeStartAddress,
        array $writeRegisters,
        int $unitId = 0,
        int $transactionId = null
    )
    {
        $this->readQuantity = $readQuantity;
        $this->writeStartAddress = $writeStartAddress;
        $this->writeRegisters = $writeRegisters;
        $this->writeRegistersBytesSize = Registers::getRegisterArrayByteSize($this->writeRegisters);
        $this->writeRegisterCount = (int)($this->writeRegistersBytesSize / 2);

        parent::__construct($readStartAddress, $unitId, $transactionId);

        $this->validate();
    }

    public function validate()
    {
        parent::validate();

        if ($this->readQuantity < 1 || $this->readQuantity > 125) {
            // there is no way more than 125 words (125*2 bytes) can be returned in response
            throw new InvalidArgumentException("read registers quantity out of range (1-125): {$this->readQuantity}");
        }

        if ($this->writeStartAddress < 0 || $this->writeStartAddress > Types::MAX_VALUE_UINT16) {
            throw new InvalidArgumentException("write registers start address out of range (0-65535): {$this->writeStartAddress}");
        }

        if ($this->writeRegisterCount === 0 || $this->writeRegisterCount > 121) {
            // there is no way more than 121 words (121*2 bytes) can be written in request
            throw new InvalidArgumentException("write registers count out of range (1-121): {$this->writeRegisterCount}");
        }
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::READ_WRITE_MULTIPLE_REGISTERS;
    }

    public function __toString(): string
    {
        return parent::__toString()
            . Types::toRegister($this->readQuantity)
            . Types::toRegister($this->writeStartAddress)
            . Types::toRegister($this->writeRegisterCount)
            . Types::toByte($this->writeRegistersBytesSize)
            . Registers::getRegisterArrayAsByteString($this->writeRegisters);
    }

    /**
     * @return array
     */
    public function getRegisters(): array
    {
        return $this->writeRegisters;
    }

    /**
     * @return int
     */
    public function getReadQuantity(): int
    {
        return $this->readQuantity;
    }

    /**
     * @return int
     */
    public function getWriteStartAddress(): int
    {
        return $this->writeStartAddress;
    }

    /**
     * @return int
     */
    public function getWriteRegisterCount(): int
    {
        return $this->writeRegisterCount;
    }

    protected function getLengthInternal(): int
    {
        // readQuantity size (2)) +
        // writeStartAddress size (2)) +
        // write registers count size (2) +
        // write register byte size (1) +
        // number of bytes registers need for data
        // = 7 bytes
        return parent::getLengthInternal() + (7 + $this->writeRegistersBytesSize);
    }
}
