<?php

namespace ModbusTcpClient\Composer\Read;

use Closure;
use ModbusTcpClient\Composer\AddressSplitter;
use ModbusTcpClient\Composer\Read\Coil\ReadCoilAddress;
use ModbusTcpClient\Composer\Read\Coil\ReadCoilAddressSplitter;
use ModbusTcpClient\Composer\Read\Coil\ReadCoilRequest;
use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputDiscretesRequest;

class ReadCoilsBuilder
{
    /** @var ReadCoilAddressSplitter */
    private $addressSplitter;

    private $addresses = [];

    /** @var string */
    private $currentUri;

    /** @var int */
    private $unitId;

    public function __construct(string $requestClass, string $uri = null, int $unitId = 0)
    {
        $this->addressSplitter = new ReadCoilAddressSplitter($requestClass);

        if ($uri !== null) {
            $this->useUri($uri);
        }
        $this->unitId = $unitId;
    }

    public static function newReadCoils(string $uri = null, int $unitId = 0): ReadCoilsBuilder
    {
        return new ReadCoilsBuilder(ReadCoilsRequest::class, $uri, $unitId);
    }

    public static function newReadInputDiscretes(string $uri = null, int $unitId = 0): ReadCoilsBuilder
    {
        return new ReadCoilsBuilder(ReadInputDiscretesRequest::class, $uri, $unitId);
    }

    public function useUri(string $uri, int $unitId = 0): ReadCoilsBuilder
    {
        if (empty($uri)) {
            throw new InvalidArgumentException('uri can not be empty value');
        }
        $this->currentUri = $uri;
        $this->unitId = $unitId;
        return $this;
    }

    protected function addAddress(ReadCoilAddress $address): ReadCoilsBuilder
    {
        if (empty($this->currentUri)) {
            throw new InvalidArgumentException('uri not set');
        }
        $unitIdPrefix = AddressSplitter::UNIT_ID_PREFIX;
        $modbusPath = "{$this->currentUri}{$unitIdPrefix}{$this->unitId}";
        $this->addresses[$modbusPath][$address->getName()] = $address;
        return $this;
    }

    public function allFromArray(array $coils): ReadCoilsBuilder
    {
        foreach ($coils as $coil) {
            if (\is_array($coil)) {
                $this->fromArray($coil);
            } elseif ($coil instanceof ReadCoilAddress) {
                $this->addAddress($coil);
            }
        }
        return $this;
    }

    public function fromArray(array $coil): ReadCoilsBuilder
    {
        $uri = $coil['uri'] ?? null;
        $unitId = $coil['unitId'] ?? 0;
        if ($uri !== null) {
            $this->useUri($uri, $unitId);
        }

        $address = $coil['address'] ?? null;
        if ($address === null) {
            throw new InvalidArgumentException('empty address given');
        }

        $callback = $coil['callback'] ?? null;
        if ($callback !== null && !($callback instanceof Closure)) {
            throw new InvalidArgumentException('callback must be a an anonymous function');
        }

        $errorCallback = $coil['errorCallback'] ?? null;
        if ($errorCallback !== null && !($errorCallback instanceof Closure)) {
            throw new InvalidArgumentException('error callback must be a an anonymous function');
        }

        $this->coil($address, $coil['name'] ?? null, $callback, $errorCallback);

        return $this;
    }

    public function coil(
        int $address,
        string $name = null,
        callable $callback = null,
        callable $errorCallback = null
    ): ReadCoilsBuilder
    {
        return $this->addAddress(new ReadCoilAddress($address, $name, $callback, $errorCallback));
    }

    /**
     * @return ReadCoilRequest[]
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





