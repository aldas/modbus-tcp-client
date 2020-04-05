<?php

namespace ModbusTcpClient\Composer\Write\Register;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Composer\RegisterAddress;
use ModbusTcpClient\Utils\Types;

class WriteRegisterAddress extends RegisterAddress
{
    /** @var int|float|string */
    private $value;

    public function __construct(int $address, string $type, $value)
    {
        parent::__construct($address, $type);

        $this->value = $value;
    }

    protected function getAllowedTypes(): array
    {
        // writing bit/byte with registers should not be allowed - word is 2 bytes so there is memory that are actually touching by writing single bit or even 1 byte
        return [
            Address::TYPE_INT16,
            Address::TYPE_UINT16,
            Address::TYPE_INT32,
            Address::TYPE_UINT32,
            Address::TYPE_INT64,
            Address::TYPE_UINT64,
            Address::TYPE_FLOAT,
        ];
    }

    /**
     * @return int|float|string
     */
    public function getValue()
    {
        return $this->value;
    }

    public function toBinary(): string
    {
        $result = "\x00\x00";
        switch ($this->type) {
            case Address::TYPE_INT16:
                $result = Types::toInt16($this->getValue());
                break;
            case Address::TYPE_UINT16:
                $result = Types::toUint16($this->getValue());
                break;
            case Address::TYPE_INT32:
                $result = Types::toInt32($this->getValue());
                break;
            case Address::TYPE_UINT32:
                $result = Types::toUint32($this->getValue());
                break;
            case Address::TYPE_INT64:
                $result = Types::toInt64($this->getValue());
                break;
            case Address::TYPE_UINT64:
                $result = Types::toUint64($this->getValue());
                break;
            case Address::TYPE_FLOAT:
                $result = Types::toReal($this->getValue());
                break;

        }
        return $result;
    }
}
