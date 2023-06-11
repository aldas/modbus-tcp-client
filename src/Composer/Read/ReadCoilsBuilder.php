<?php
declare(strict_types=1);

namespace ModbusTcpClient\Composer\Read;

use Closure;
use ModbusTcpClient\Composer\AddressSplitter;
use ModbusTcpClient\Composer\Range;
use ModbusTcpClient\Composer\Read\Coil\ReadCoilAddress;
use ModbusTcpClient\Composer\Read\Coil\ReadCoilAddressSplitter;
use ModbusTcpClient\Composer\Request;
use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputDiscretesRequest;

class ReadCoilsBuilder
{
    /** @var ReadCoilAddressSplitter */
    protected ReadCoilAddressSplitter $addressSplitter;

    /**
     * @var array<array<string,ReadCoilAddress>>
     */
    private array $addresses = [];

    /**
     * @var array<Range[]>
     */
    private array $unaddressableRanges = [];

    /** @var string */
    private string $currentUri;

    /** @var int */
    private int $unitId;

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

    /**
     * unaddressableRanges are address ranges that Modbus server does not allow to be read. By settings unaddressable
     * range(s) address splitter can avoid including these ranges into requests (if possible).
     * Range min and max values are inclusive.
     *
     * Example: `[ [100,110], [256, 300], [512] ]` this will add 3 ranges.
     *
     *
     * @param array<array<int>> $ranges
     * @return $this
     */
    public function unaddressableRanges(array $ranges): ReadCoilsBuilder
    {
        if (count($ranges) == 0) {
            return $this;
        }
        if (empty($this->currentUri)) {
            throw new InvalidArgumentException('unaddressable ranges can not be added when uri is empty');
        }

        $unitIdPrefix = AddressSplitter::UNIT_ID_PREFIX;
        $modbusPath = "{$this->currentUri}{$unitIdPrefix}{$this->unitId}";

        $tmpRanges = [];
        foreach ($ranges as $range) {
            $tmpRanges[] = Range::fromIntArray($range);
        }
        $this->unaddressableRanges[$modbusPath] = $tmpRanges;

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

    /**
     * @param array<array<string,mixed>|ReadCoilAddress> $coils
     * @return $this
     */
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

    /**
     * @param array<string,mixed> $coil
     * @return $this
     */
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
        int      $address,
        string   $name = null,
        callable $callback = null,
        callable $errorCallback = null
    ): ReadCoilsBuilder
    {
        return $this->addAddress(new ReadCoilAddress($address, $name, $callback, $errorCallback));
    }

    /**
     * @return Request[]
     */
    public function build(): array
    {
        return $this->addressSplitter->splitWithUnaddressableRanges($this->addresses, $this->unaddressableRanges);
    }

    public function isNotEmpty(): bool
    {
        return !empty($this->addresses);
    }
}





