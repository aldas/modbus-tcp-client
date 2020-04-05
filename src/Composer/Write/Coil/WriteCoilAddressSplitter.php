<?php

namespace ModbusTcpClient\Composer\Write\Coil;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Composer\AddressSplitter;
use ModbusTcpClient\Exception\InvalidArgumentException;

class WriteCoilAddressSplitter extends AddressSplitter
{
    /** @var string */
    private $requestClass;

    public function __construct(string $requestClass)
    {
        $this->requestClass = $requestClass;
    }

    /**
     * @param string $uri
     * @param WriteCoilAddress[] $addressesChunk
     * @param int $startAddress
     * @param int $quantity
     * @param int $unitId
     * @return WriteCoilRequest
     */
    protected function createRequest(string $uri, array $addressesChunk, int $startAddress, int $quantity, int $unitId = 0)
    {
        $values = [];
        foreach ($addressesChunk as $address) {
            $values[] = $address->getValue();
        }

        return new WriteCoilRequest($uri, $addressesChunk, new $this->requestClass($startAddress, $values, $unitId));
    }

    protected function getMaxAddressesPerModbusRequest(): int
    {
        return static::MAX_COILS_PER_MODBUS_REQUEST;
    }

    protected function shouldSplit(Address $currentAddress, int $currentQuantity, Address $previousAddress = null, int $previousQuantity = null): bool
    {
        $isOverAddressLimit = $currentQuantity >= $this->getMaxAddressesPerModbusRequest();
        if ($isOverAddressLimit) {
            return $isOverAddressLimit;
        }
        if ($previousAddress === null) {
            return false;
        }

        $currentStartAddress = $currentAddress->getAddress();
        $previousStartAddress = $previousAddress->getAddress();
        $previousAddressEndStartAddress = ($previousStartAddress + $previousAddress->getSize());

        if (($previousStartAddress <= $currentStartAddress) && ($currentStartAddress < $previousAddressEndStartAddress)) {
            // situation when current address overlaps previous memory range does not make sense

            $info = "{$previousStartAddress} with {$currentStartAddress}";
            throw new InvalidArgumentException('Trying to write addresses that seem share their memory range! ' . $info);
        }

        // current and previous need to be adjacent as WriteMultipleCoilsRequest needs to have all registers in packet to be adjacent
        // or another packet should be build (split)
        return $currentStartAddress - $previousAddressEndStartAddress > 0;
    }
}
