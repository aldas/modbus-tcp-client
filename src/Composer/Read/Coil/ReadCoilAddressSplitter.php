<?php

namespace ModbusTcpClient\Composer\Read\Coil;


use ModbusTcpClient\Composer\AddressSplitter;

class ReadCoilAddressSplitter extends AddressSplitter
{
    /** @var string */
    private $requestClass;

    public function __construct(string $requestClass)
    {
        $this->requestClass = $requestClass;
    }

    protected function getMaxAddressesPerModbusRequest(): int
    {
        return static::MAX_COILS_PER_MODBUS_REQUEST;
    }

    protected function createRequest(string $uri, array $addressesChunk, int $startAddress, int $quantity, int $unitId = 0)
    {
        return new ReadCoilRequest($uri, $addressesChunk, new $this->requestClass($startAddress, $quantity, $unitId));
    }
}
