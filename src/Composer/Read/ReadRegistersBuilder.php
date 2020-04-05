<?php

namespace ModbusTcpClient\Composer\Read;

use Closure;
use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Composer\AddressSplitter;
use ModbusTcpClient\Composer\Read\Register\BitReadRegisterAddress;
use ModbusTcpClient\Composer\Read\Register\ByteReadRegisterAddress;
use ModbusTcpClient\Composer\Read\Register\ReadRegisterAddress;
use ModbusTcpClient\Composer\Read\Register\ReadRegisterAddressSplitter;
use ModbusTcpClient\Composer\Read\Register\ReadRegisterRequest;
use ModbusTcpClient\Composer\Read\Register\StringReadRegisterAddress;
use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersRequest;

class ReadRegistersBuilder
{
    /** @var ReadRegisterAddressSplitter */
    private $addressSplitter;

    private $addresses = [];

    /** @var string */
    private $currentUri;

    /** @var int */
    private $unitId;

    public function __construct(string $requestClass, string $uri = null, int $unitId = 0)
    {
        $this->addressSplitter = new ReadRegisterAddressSplitter($requestClass);

        if ($uri !== null) {
            $this->useUri($uri);
        }
        $this->unitId = $unitId;
    }

    public static function newReadHoldingRegisters(string $uri = null, int $unitId = 0): ReadRegistersBuilder
    {
        return new ReadRegistersBuilder(ReadHoldingRegistersRequest::class, $uri, $unitId);
    }

    public static function newReadInputRegisters(string $uri = null, int $unitId = 0): ReadRegistersBuilder
    {
        return new ReadRegistersBuilder(ReadInputRegistersRequest::class, $uri, $unitId);
    }

    public function useUri(string $uri, int $unitId = 0): ReadRegistersBuilder
    {
        if (empty($uri)) {
            throw new InvalidArgumentException('uri can not be empty value');
        }
        $this->currentUri = $uri;
        $this->unitId = $unitId;
        return $this;
    }

    protected function addAddress(ReadRegisterAddress $address): ReadRegistersBuilder
    {
        if (empty($this->currentUri)) {
            throw new InvalidArgumentException('uri not set');
        }
        $unitIdPrefix = AddressSplitter::UNIT_ID_PREFIX;
        $modbusPath = "{$this->currentUri}{$unitIdPrefix}{$this->unitId}";
        $this->addresses[$modbusPath][$address->getName()] = $address;
        return $this;
    }

    public function allFromArray(array $registers): ReadRegistersBuilder
    {
        foreach ($registers as $register) {
            if (\is_array($register)) {
                $this->fromArray($register);
            } elseif ($register instanceof ReadRegisterAddress) {
                $this->addAddress($register);
            }
        }
        return $this;
    }

    public function fromArray(array $register): ReadRegistersBuilder
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

        $callback = $register['callback'] ?? null;
        if ($callback !== null && !($callback instanceof Closure)) {
            throw new InvalidArgumentException('callback must be a an anonymous function');
        }

        $errorCallback = $register['errorCallback'] ?? null;
        if ($errorCallback !== null && !($errorCallback instanceof Closure)) {
            throw new InvalidArgumentException('error callback must be a an anonymous function');
        }

        $addressType = strtolower($register['type'] ?? null);
        if (empty($addressType) || !\in_array($addressType, Address::TYPES, true)) {
            throw new InvalidArgumentException('empty or unknown type for address given');
        }

        switch ($addressType) {
            case Address::TYPE_BIT:
                $this->bit($address, $register['bit'] ?? 0, $register['name'] ?? null, $callback, $errorCallback);
                break;
            case Address::TYPE_BYTE:
                $this->byte($address, (bool)($register['firstByte'] ?? true), $register['name'] ?? null, $callback, $errorCallback);
                break;
            case Address::TYPE_INT16:
                $this->int16($address, $register['name'] ?? null, $callback, $errorCallback);
                break;
            case Address::TYPE_UINT16:
                $this->uint16($address, $register['name'] ?? null, $callback, $errorCallback);
                break;
            case Address::TYPE_INT32:
                $this->int32($address, $register['name'] ?? null, $callback, $errorCallback);
                break;
            case Address::TYPE_UINT32:
                $this->uint32($address, $register['name'] ?? null, $callback, $errorCallback);
                break;
            case Address::TYPE_INT64:
                $this->int64($address, $register['name'] ?? null, $callback, $errorCallback);
                break;
            case Address::TYPE_UINT64:
                $this->uint64($address, $register['name'] ?? null, $callback, $errorCallback);
                break;
            case Address::TYPE_FLOAT:
                $this->float($address, $register['name'] ?? null, $callback, $errorCallback);
                break;
            case Address::TYPE_STRING:
                $byteLength = $register['length'] ?? null;
                if ($byteLength === null) {
                    throw new InvalidArgumentException('missing length for string address');
                }
                $this->string($address, $byteLength, $register['name'] ?? null, $callback, $errorCallback);
                break;
        }
        return $this;
    }

    public function bit(int $address, int $nthBit, string $name = null, callable $callback = null, callable $errorCallback = null): ReadRegistersBuilder
    {
        if ($nthBit < 0 || $nthBit > 15) {
            throw new InvalidArgumentException("Invalid bit number in for register given! nthBit: '{$nthBit}', address: {$address}");
        }
        return $this->addAddress(new BitReadRegisterAddress($address, $nthBit, $name, $callback, $errorCallback));
    }

    public function byte(int $address, bool $firstByte = true, string $name = null, callable $callback = null, callable $errorCallback = null): ReadRegistersBuilder
    {
        return $this->addAddress(new ByteReadRegisterAddress($address, $firstByte, $name, $callback, $errorCallback));
    }

    public function int16(int $address, string $name = null, callable $callback = null, callable $errorCallback = null): ReadRegistersBuilder
    {
        return $this->addAddress(new ReadRegisterAddress($address, ReadRegisterAddress::TYPE_INT16, $name, $callback, $errorCallback));
    }

    public function uint16(int $address, string $name = null, callable $callback = null, callable $errorCallback = null): ReadRegistersBuilder
    {
        return $this->addAddress(new ReadRegisterAddress($address, ReadRegisterAddress::TYPE_UINT16, $name, $callback, $errorCallback));
    }

    public function int32(int $address, string $name = null, callable $callback = null, callable $errorCallback = null): ReadRegistersBuilder
    {
        return $this->addAddress(new ReadRegisterAddress($address, ReadRegisterAddress::TYPE_INT32, $name, $callback, $errorCallback));
    }

    public function uint32(int $address, string $name = null, callable $callback = null, callable $errorCallback = null): ReadRegistersBuilder
    {
        return $this->addAddress(new ReadRegisterAddress($address, ReadRegisterAddress::TYPE_UINT32, $name, $callback, $errorCallback));
    }

    public function uint64(int $address, string $name = null, callable $callback = null, callable $errorCallback = null): ReadRegistersBuilder
    {
        return $this->addAddress(new ReadRegisterAddress($address, ReadRegisterAddress::TYPE_UINT64, $name, $callback, $errorCallback));
    }

    public function int64(int $address, string $name = null, callable $callback = null, callable $errorCallback = null): ReadRegistersBuilder
    {
        return $this->addAddress(new ReadRegisterAddress($address, ReadRegisterAddress::TYPE_INT64, $name, $callback, $errorCallback));
    }

    public function float(int $address, string $name = null, callable $callback = null, callable $errorCallback = null): ReadRegistersBuilder
    {
        return $this->addAddress(new ReadRegisterAddress($address, ReadRegisterAddress::TYPE_FLOAT, $name, $callback, $errorCallback));
    }

    public function string(int $address, int $byteLength, string $name = null, callable $callback = null, callable $errorCallback = null): ReadRegistersBuilder
    {
        if ($byteLength < 1 || $byteLength > 228) {
            throw new InvalidArgumentException("Out of range string length for given! length: '{$byteLength}', address: {$address}");
        }
        return $this->addAddress(new StringReadRegisterAddress($address, $byteLength, $name, $callback, $errorCallback));
    }

    /**
     * @return ReadRegisterRequest[]
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





