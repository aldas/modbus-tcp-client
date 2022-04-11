<?php
declare(strict_types=1);

namespace ModbusTcpClient\Composer\Read\Register;

use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersRequest;

class BitReadRegisterAddress extends ReadRegisterAddress
{
    /** @var int */
    private int $bit;

    public function __construct(int $address, int $bit, string $name = null, callable $callback = null, callable $errorCallback = null)
    {
        $type = Address::TYPE_BIT;
        parent::__construct($address, $type, $name ?: "{$type}_{$address}_{$bit}", $callback, $errorCallback);
        $this->bit = $bit;
    }

    protected function extractInternal(ReadHoldingRegistersResponse|ReadInputRegistersRequest $response): mixed
    {
        return $response->getWordAt($this->address)->isBitSet($this->bit);
    }

    public function getBit(): int
    {
        return $this->bit;
    }

    protected function getAllowedTypes(): array
    {
        return [Address::TYPE_BIT];
    }
}
