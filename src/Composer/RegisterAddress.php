<?php
declare(strict_types=1);

namespace ModbusTcpClient\Composer;


use ModbusTcpClient\Exception\InvalidArgumentException;

abstract class RegisterAddress implements Address
{
    /** @var int */
    protected int $address;

    /** @var string */
    protected string $type;

    /**
     * @return string[]
     */
    protected abstract function getAllowedTypes(): array;

    public function __construct(int $address, string $type)
    {
        $this->address = $address;
        $this->type = $type;

        if (!in_array($type, $this->getAllowedTypes(), true)) {
            throw new InvalidArgumentException("Invalid address type given! type: '{$type}', address: {$address}");
        }
    }

    public function getAddress(): int
    {
        return $this->address;
    }

    public function getSize(): int
    {
        $size = 1;
        switch ($this->type) {
            case Address::TYPE_INT32:
            case Address::TYPE_UINT32:
            case Address::TYPE_FLOAT:
                $size = 2;
                break;
            case Address::TYPE_INT64:
            case Address::TYPE_UINT64:
            case Address::TYPE_DOUBLE:
                $size = 4;
                break;
        }
        return $size;
    }

    public function getType(): string
    {
        return $this->type;
    }
}
