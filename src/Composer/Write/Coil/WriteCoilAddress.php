<?php
declare(strict_types=1);

namespace ModbusTcpClient\Composer\Write\Coil;


use ModbusTcpClient\Composer\Address;

class WriteCoilAddress implements Address
{
    /** @var int */
    protected int $address;

    /** @var bool */
    private bool $value;

    public function __construct(int $address, bool $value)
    {
        $this->address = $address;
        $this->value = $value;
    }

    public function getValue(): bool
    {
        return $this->value;
    }

    public function getSize(): int
    {
        return 1;
    }

    public function getAddress(): int
    {
        return $this->address;
    }

    public function getType(): string
    {
        return Address::TYPE_BIT;
    }
}
