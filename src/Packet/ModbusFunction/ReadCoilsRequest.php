<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;


use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusRequest;
use ModbusTcpClient\Packet\ProtocolDataUnitRequest;
use ModbusTcpClient\Utils\Types;

/**
 * Request for Read Coils (FC=01)
 */
class ReadCoilsRequest extends ProtocolDataUnitRequest implements ModbusRequest
{
    /**
     * @var int total number of coils requested. Size 2 bytes
     */
    private $quantity;

    public function __construct(int $startAddress, int $quantity, int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($startAddress, $unitId, $transactionId);
        $this->quantity = $quantity;

        $this->validate();
    }

    public function validate()
    {
        parent::validate();

        if ((null !== $this->quantity) && ($this->quantity > 0 && $this->quantity <= 2048)) {
            // 2048 coils is due that in response data size field is 1 byte so max 256*8=2048 coils can be returned
            return;
        }
        throw new InvalidArgumentException("quantity is not set or out of range (1-2048): {$this->quantity}");
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::READ_COILS;
    }

    public function __toString(): string
    {
        return parent::__toString()
            . Types::toRegister($this->getQuantity());
    }

    /**
     * @return int
     */
    public function getQuantity(): int
    {
        return $this->quantity;
    }

    protected function getLengthInternal(): int
    {
        return parent::getLengthInternal() + 2; // quantity size (2 bytes)
    }
}