<?php

namespace ModbusTcpClient\Composer\Read;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Packet\ModbusResponse;

class StringReadAddress extends ReadAddress
{
    /** @var int */
    private $byteLength;

    public function __construct(int $address, int $byteLength, string $name = null, callable $callback = null, callable $errorCallback = null)
    {
        $type = Address::TYPE_STRING;
        parent::__construct($address, $type, $name ?: "{$type}_{$address}_{$byteLength}", $callback, $errorCallback);
        $this->byteLength = $byteLength;
    }

    protected function extractInternal(ModbusResponse $response)
    {
        return $response->getAsciiStringAt($this->address, $this->byteLength);
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