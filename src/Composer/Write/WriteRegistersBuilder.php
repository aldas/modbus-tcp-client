<?php

namespace ModbusTcpClient\Composer\Write;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Composer\AddressSplitter;
use ModbusTcpClient\Composer\Write\Register\StringWriteRegisterAddress;
use ModbusTcpClient\Composer\Write\Register\WriteRegisterAddress;
use ModbusTcpClient\Composer\Write\Register\WriteRegisterAddressSplitter;
use ModbusTcpClient\Composer\Write\Register\WriteRegisterRequest;
use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleRegistersRequest;

class WriteRegistersBuilder
{
    /** @var WriteRegisterAddressSplitter */
    private $addressSplitter;

    /** @var WriteRegisterAddress[] */
    private $addresses = [];

    /** @var string */
    private $currentUri;

    /** @var int */
    private $unitId;

    public function __construct(string $requestClass, string $uri = null, int $unitId = 0)
    {
        $this->addressSplitter = new WriteRegisterAddressSplitter($requestClass);

        if ($uri !== null) {
            $this->useUri($uri);
        }
        $this->unitId = $unitId;
    }

    public static function newWriteMultipleRegisters(string $uri = null, int $unitId = 0): WriteRegistersBuilder
    {
        return new WriteRegistersBuilder(WriteMultipleRegistersRequest::class, $uri, $unitId);
    }

    public function useUri(string $uri, int $unitId = 0): WriteRegistersBuilder
    {
        if (empty($uri)) {
            throw new InvalidArgumentException('uri can not be empty value');
        }
        $this->currentUri = $uri;
        $this->unitId = $unitId;

        return $this;
    }

    protected function addAddress(WriteRegisterAddress $address): WriteRegistersBuilder
    {
        if (empty($this->currentUri)) {
            throw new InvalidArgumentException('uri not set');
        }
        $unitIdPrefix = AddressSplitter::UNIT_ID_PREFIX;
        $modbusPath = "{$this->currentUri}{$unitIdPrefix}{$this->unitId}";
        $this->addresses[$modbusPath][$address->getAddress()] = $address;
        return $this;
    }

    public function allFromArray(array $registers): WriteRegistersBuilder
    {
        foreach ($registers as $register) {
            if (\is_array($register)) {
                $this->fromArray($register);
            } elseif ($register instanceof WriteRegisterAddress) {
                $this->addAddress($register);
            }
        }
        return $this;
    }

    public function fromArray(array $register): WriteRegistersBuilder
    {
        $uri = $register['uri'] ?? null;
        $unitId = $register['unitId'] ?? 0;
        if ($uri !== null) {
            $this->useUri($uri, $unitId);
        }

        $address = $register['address'] ?? null;
        if ($address === null) {
            throw new InvalidArgumentException('empty address given');
        }

        $addressType = strtolower($register['type'] ?? null);
        if (empty($addressType) || !\in_array($addressType, Address::TYPES, true)) {
            throw new InvalidArgumentException('empty or unknown type for address given');
        }

        if (!array_key_exists('value', $register)) {
            throw new InvalidArgumentException('value missing');
        }

        $value = $register['value'];

        switch ($addressType) {
            case Address::TYPE_BIT:
            case Address::TYPE_BYTE:
                throw new InvalidArgumentException('writing bit/byte through register is not supported as 1 word is 2 bytes so we are touching more memory than needed');
                break;
            case Address::TYPE_INT16:
                $this->int16($address, $value);
                break;
            case Address::TYPE_UINT16:
                $this->uint16($address, $value);
                break;
            case Address::TYPE_INT32:
                $this->int32($address, $value);
                break;
            case Address::TYPE_UINT32:
                $this->uint32($address, $value);
                break;
            case Address::TYPE_INT64:
                $this->int64($address, $value);
                break;
            case Address::TYPE_UINT64:
                $this->uint64($address, $value);
                break;
            case Address::TYPE_FLOAT:
                $this->float($address, $value);
                break;
            case Address::TYPE_STRING:
                $byteLength = $register['length'] ?? null;
                if ($byteLength === null) {
                    throw new InvalidArgumentException('missing length for string address');
                }
                $this->string($address, $value, $byteLength);
                break;
        }
        return $this;
    }

    public function int16(int $address, int $value): WriteRegistersBuilder
    {
        return $this->addAddress(new WriteRegisterAddress($address, Address::TYPE_INT16, $value));
    }

    public function uint16(int $address, int $value): WriteRegistersBuilder
    {
        return $this->addAddress(new WriteRegisterAddress($address, Address::TYPE_UINT16, $value));
    }

    public function int32(int $address, int $value): WriteRegistersBuilder
    {
        return $this->addAddress(new WriteRegisterAddress($address, Address::TYPE_INT32, $value));
    }

    public function uint32(int $address, int $value): WriteRegistersBuilder
    {
        return $this->addAddress(new WriteRegisterAddress($address, Address::TYPE_UINT32, $value));
    }

    public function uint64(int $address, int $value): WriteRegistersBuilder
    {
        return $this->addAddress(new WriteRegisterAddress($address, Address::TYPE_UINT64, $value));
    }

    public function int64(int $address, int $value): WriteRegistersBuilder
    {
        return $this->addAddress(new WriteRegisterAddress($address, Address::TYPE_INT64, $value));
    }

    public function float(int $address, float $value): WriteRegistersBuilder
    {
        return $this->addAddress(new WriteRegisterAddress($address, Address::TYPE_FLOAT, $value));
    }

    public function string(int $address, string $string, int $byteLength = null): WriteRegistersBuilder
    {
        return $this->addAddress(new StringWriteRegisterAddress($address, $string, $byteLength));
    }

    /**
     * @return WriteRegisterRequest[]
     */
    public function build(): array
    {
        return $this->addressSplitter->split($this->addresses);
    }

    public function isNotEmpty()
    {
        return !empty($this->addresses);
    }
}
