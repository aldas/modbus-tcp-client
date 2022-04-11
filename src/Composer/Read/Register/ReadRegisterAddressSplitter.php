<?php
declare(strict_types=1);

namespace ModbusTcpClient\Composer\Read\Register;


use ModbusTcpClient\Composer\AddressSplitter;

class ReadRegisterAddressSplitter extends AddressSplitter
{
    /** @var string */
    private string $requestClass;

    public function __construct(string $requestClass)
    {
        $this->requestClass = $requestClass;
    }

    /**
     * @param string $uri
     * @param ReadRegisterAddress[] $addressesChunk
     * @param int $startAddress
     * @param int $quantity
     * @param int $unitId
     * @return ReadRegisterRequest
     */
    protected function createRequest(string $uri, array $addressesChunk, int $startAddress, int $quantity, int $unitId = 0): ReadRegisterRequest
    {
        return new ReadRegisterRequest($uri, $addressesChunk, new $this->requestClass($startAddress, $quantity, $unitId));
    }
}
