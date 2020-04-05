<?php

namespace ModbusTcpClient\Composer\Write\Register;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Utils\Types;

class StringWriteRegisterAddress extends WriteRegisterAddress
{
    /**
     * @var int
     */
    private $byteLength;

    /**
     * @var string
     */
    private $toEncoding;

    public function __construct(int $address, string $value, int $byteLength, string $toEncoding = null)
    {
        parent::__construct($address, Address::TYPE_STRING, $value);

        if ($byteLength < 1 || $byteLength > 228) {
            throw new InvalidArgumentException("Out of range string length for given! length: '{$byteLength}', address: {$address}");
        }

        $this->byteLength = $byteLength ?? strlen($value);
        $this->toEncoding = $toEncoding;
    }

    protected function getAllowedTypes(): array
    {
        return [Address::TYPE_STRING];
    }

    public function getSize(): int
    {
        return ceil($this->byteLength / 2) ?: 1;
    }

    public function toBinary(): string
    {
        return Types::toString($this->getValue(), $this->getSize(), $this->toEncoding);
    }

}
