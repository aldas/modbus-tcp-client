<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusRequest;
use ModbusTcpClient\Packet\ProtocolDataUnitRequest;
use ModbusTcpClient\Utils\Types;

/**
 * Request for Write Multiple Coils (FC=15)
 */
class WriteMultipleCoilsRequest extends ProtocolDataUnitRequest implements ModbusRequest
{
    /**
     * @var array coils (array of booleans)
     */
    private $coils;
    private $coilCount;
    private $coilBytesSize;

    public function __construct(int $startAddress, array $coils, int $unitId = 0, int $transactionId = null)
    {
        $this->coils = $coils;
        $this->coilCount = count($this->coils);
        $this->coilBytesSize = (int)(($this->coilCount + 7) / 8);

        parent::__construct($startAddress, $unitId, $transactionId);

        $this->validate();
    }

    public function validate()
    {
        parent::validate();

        if ($this->coilCount === 0 || $this->coilCount > 2048) {
            throw new InvalidArgumentException("coils count out of range (1-2048): {$this->coilCount}");
        }
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::WRITE_MULTIPLE_COILS;
    }

    public function __toString(): string
    {
        return parent::__toString()
            . Types::toRegister($this->coilCount)
            . Types::toByte($this->coilBytesSize)
            . Types::byteArrayToByte(Types::booleanArrayToByteArray($this->coils));
    }

    /**
     * @return array
     */
    public function getCoils(): array
    {
        return $this->coils;
    }

    protected function getLengthInternal(): int
    {
        return parent::getLengthInternal() + (3 + $this->coilBytesSize); // coilCount + coilBytesSize + number of bytes coils need for data
    }
}