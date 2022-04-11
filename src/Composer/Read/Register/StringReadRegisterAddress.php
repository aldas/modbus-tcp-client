<?php
declare(strict_types=1);

namespace ModbusTcpClient\Composer\Read\Register;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersRequest;

class StringReadRegisterAddress extends ReadRegisterAddress
{
    /** @var int */
    private int $byteLength;

    public function __construct(
        int      $address,
        int      $byteLength,
        string   $name = null,
        callable $callback = null,
        callable $errorCallback = null,
        int      $endian = null
    )
    {
        $type = Address::TYPE_STRING;
        parent::__construct($address, $type, $name ?: "{$type}_{$address}_{$byteLength}", $callback, $errorCallback, $endian);
        $this->byteLength = $byteLength;
    }

    protected function extractInternal(ReadHoldingRegistersResponse|ReadInputRegistersRequest $response): mixed
    {
        return $response->getAsciiStringAt($this->address, $this->byteLength, $this->getEndian());
    }

    public function getSize(): int
    {
        return (int)ceil($this->byteLength / 2); // 1 register contains 2 bytes/chars
    }

    protected function getAllowedTypes(): array
    {
        return [Address::TYPE_STRING];
    }
}
