<?php

namespace ModbusTcpClient\Composer;


abstract class AddressSplitter
{
    const UNIT_ID_PREFIX = '||unitId=';

    const MAX_REGISTERS_PER_MODBUS_REQUEST = 124;
    const MAX_COILS_PER_MODBUS_REQUEST = 2048; // response has 1 byte field for count - so 256 * 8 is max

    protected function getMaxAddressesPerModbusRequest(): int
    {
        return static::MAX_REGISTERS_PER_MODBUS_REQUEST;
    }

    abstract protected function createRequest(string $uri, array $addressesChunk, int $startAddress, int $quantity, int $unitId);

    /**
     * @param Address[] $addresses
     * @return array
     */
    public function split(array $addresses): array
    {
        $result = [];
        foreach ($addresses as $modbusPath => $addrs) {
            $pathParts = explode(static::UNIT_ID_PREFIX, $modbusPath);
            $uri = $pathParts[0];
            $unitId = $pathParts[1];
            // sort by address and size to help chunking
            usort($addrs, function (Address $a, Address $b) {
                $aAddr = $a->getAddress();
                $bAddr = $b->getAddress();
                if ($aAddr === $bAddr) {
                    $sizeCmp = $a->getSize() <=> $b->getSize();
                    return $sizeCmp !== 0 ? $sizeCmp : $a->getType() <=> $b->getType();
                }
                return $aAddr <=> $bAddr;

            });

            $startAddress = null;
            $quantity = null;
            $chunk = [];
            $previousAddress = null;
            $maxAvailableRegister = null;
            foreach ($addrs as $currentAddress) {
                /** @var Address $currentAddress */
                $currentStartAddress = $currentAddress->getAddress();
                if ($startAddress === null) {
                    $startAddress = $currentStartAddress;
                }

                $nextAvailableRegister = $currentStartAddress + $currentAddress->getSize();

                // in case next address is smaller than previous address with its size we need to make sure that quantity does not change
                // as those addresses overlap
                if ($maxAvailableRegister === null || $nextAvailableRegister > $maxAvailableRegister) {
                    $maxAvailableRegister = $nextAvailableRegister;
                } else if ($nextAvailableRegister < $maxAvailableRegister) {
                    $nextAvailableRegister = $maxAvailableRegister;
                }
                $previousQuantity = $quantity;
                $quantity = $nextAvailableRegister - $startAddress;
                if ($this->shouldSplit($currentAddress, $quantity, $previousAddress, $previousQuantity)) {
                    $result[] = $this->createRequest($uri, $chunk, $startAddress, $previousQuantity, $unitId);

                    $chunk = [];
                    $maxAvailableRegister = null;
                    $startAddress = $currentStartAddress;
                    $quantity = $currentAddress->getSize();
                }
                $chunk[] = $currentAddress;
                $previousAddress = $currentAddress;
            }

            if (!empty($chunk)) {
                $result[] = $this->createRequest($uri, $chunk, $startAddress, $quantity, $unitId);
            }
        }
        return $result;
    }

    protected function shouldSplit(Address $currentAddress, int $currentQuantity, Address $previousAddress = null, int $previousQuantity = null): bool
    {
        return $currentQuantity >= $this->getMaxAddressesPerModbusRequest();
    }

}
