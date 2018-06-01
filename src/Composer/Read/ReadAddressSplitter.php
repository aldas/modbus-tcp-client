<?php

namespace ModbusTcpClient\Composer\Read;


use ModbusTcpClient\Composer\AddressSplitter;

class ReadAddressSplitter extends AddressSplitter
{
    /** @var string */
    private $requestClass;

    public function __construct(string $requestClass)
    {
        $this->requestClass = $requestClass;
    }

    protected function createRequest(string $uri, array $addressesChunk, int $startAddress, int $quantity, int $unitId = 0)
    {
        return new ReadRequest($uri, $addressesChunk, new $this->requestClass($startAddress, $quantity, $unitId));
    }
}