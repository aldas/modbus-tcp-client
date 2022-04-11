<?php
declare(strict_types=1);

namespace ModbusTcpClient\Composer\Read\Coil;


use ModbusTcpClient\Composer\AddressSplitter;

class ReadCoilAddressSplitter extends AddressSplitter
{
    /** @var string */
    private string $requestClass;

    public function __construct(string $requestClass)
    {
        $this->requestClass = $requestClass;
    }

    protected function getMaxAddressesPerModbusRequest(): int
    {
        return static::MAX_COILS_PER_MODBUS_REQUEST;
    }

    /**
     * @param string $uri
     * @param ReadCoilAddress[] $addressesChunk
     * @param int $startAddress
     * @param int $quantity
     * @param int $unitId
     * @return ReadCoilRequest
     */
    protected function createRequest(string $uri, array $addressesChunk, int $startAddress, int $quantity, int $unitId = 0): ReadCoilRequest
    {
        return new ReadCoilRequest($uri, $addressesChunk, new $this->requestClass($startAddress, $quantity, $unitId));
    }
}
