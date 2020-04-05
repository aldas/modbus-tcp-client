<?php

namespace ModbusTcpClient\Composer\Write;


use ModbusTcpClient\Composer\AddressSplitter;
use ModbusTcpClient\Composer\Write\Coil\WriteCoilAddress;
use ModbusTcpClient\Composer\Write\Coil\WriteCoilAddressSplitter;
use ModbusTcpClient\Composer\Write\Coil\WriteCoilRequest;
use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleCoilsRequest;

class WriteCoilsBuilder
{
    /** @var WriteCoilAddressSplitter */
    private $addressSplitter;

    /** @var WriteCoilAddress[] */
    private $addresses = [];

    /** @var string */
    private $currentUri;

    /** @var int */
    private $unitId;

    public function __construct(string $requestClass, string $uri = null, int $unitId = 0)
    {
        $this->addressSplitter = new WriteCoilAddressSplitter($requestClass);

        if ($uri !== null) {
            $this->useUri($uri);
        }
        $this->unitId = $unitId;
    }

    public static function newWriteMultipleCoils(string $uri = null, int $unitId = 0): WriteCoilsBuilder
    {
        return new WriteCoilsBuilder(WriteMultipleCoilsRequest::class, $uri, $unitId);
    }

    public function useUri(string $uri, int $unitId = 0): WriteCoilsBuilder
    {
        if (empty($uri)) {
            throw new InvalidArgumentException('uri can not be empty value');
        }
        $this->currentUri = $uri;
        $this->unitId = $unitId;

        return $this;
    }

    protected function addAddress(WriteCoilAddress $address): WriteCoilsBuilder
    {
        if (empty($this->currentUri)) {
            throw new InvalidArgumentException('uri not set');
        }
        $unitIdPrefix = AddressSplitter::UNIT_ID_PREFIX;
        $modbusPath = "{$this->currentUri}{$unitIdPrefix}{$this->unitId}";
        $this->addresses[$modbusPath][$address->getAddress()] = $address;
        return $this;
    }

    public function allFromArray(array $coils): WriteCoilsBuilder
    {
        foreach ($coils as $coil) {
            if (\is_array($coil)) {
                $this->fromArray($coil);
            } elseif ($coil instanceof WriteCoilAddress) {
                $this->addAddress($coil);
            }
        }
        return $this;
    }

    public function fromArray(array $coil): WriteCoilsBuilder
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

        if (!array_key_exists('value', $coil)) {
            throw new InvalidArgumentException('value missing');
        }

        $this->coil($address, (bool)$coil['value']);

        return $this;
    }

    public function coil(int $address, bool $value): WriteCoilsBuilder
    {
        return $this->addAddress(new WriteCoilAddress($address, $value));
    }

    /**
     * @return WriteCoilRequest[]
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
