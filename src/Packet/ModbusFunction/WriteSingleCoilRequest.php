<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusRequest;
use ModbusTcpClient\Packet\ProtocolDataUnitRequest;
use ModbusTcpClient\Utils\Types;


/**
 * Request for Write Single Coil (FC=05)
 */
class WriteSingleCoilRequest extends ProtocolDataUnitRequest implements ModbusRequest
{
    const ON = 0xFF;
    const OFF = 0x0;

    /**
     * @var bool value to be sent to modbus
     */
    private $coil;

    public function __construct(int $startAddress, bool $coil, int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($startAddress, $unitId, $transactionId);
        $this->coil = $coil;

        $this->validate();
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::WRITE_SINGLE_COIL;
    }

    public function __toString(): string
    {
        return parent::__toString()
            . Types::toByte($this->isCoil() ? self::ON : self::OFF)
            . chr(0x0);
    }

    /**
     * @return bool
     */
    public function isCoil(): bool
    {
        return $this->coil;
    }

    protected function getLengthInternal(): int
    {
        return parent::getLengthInternal() + 2; // coil size (1 byte + 1 byte)
    }
}