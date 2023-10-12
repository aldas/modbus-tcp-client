<?php
declare(strict_types=1);

namespace ModbusTcpClient\Composer;

use ModbusTcpClient\Composer\Read\Register\ByteReadRegisterAddress;
use ModbusTcpClient\Exception\InvalidArgumentException;

abstract class AddressSplitter
{
    /**
     * @var Range[]
     */
    private array $currentUnaddressableRanges = [];

    const UNIT_ID_PREFIX = '||unitId=';

    const MAX_REGISTERS_PER_MODBUS_REQUEST = 124;
    const MAX_COILS_PER_MODBUS_REQUEST = 2048; // response has 1 byte field for count - so 256 * 8 is max

    protected function getMaxAddressesPerModbusRequest(): int
    {
        return static::MAX_REGISTERS_PER_MODBUS_REQUEST;
    }

    /**
     * currentUnaddressableRanges contains temporary values during `split` function execution so we can access current
     * modbus server ranges inside `shouldSplit` function.
     *
     * @return Range[]
     */
    protected function currentUnaddressableRanges(): array
    {
        return $this->currentUnaddressableRanges;
    }

    /**
     * @param string $uri
     * @param Address[] $addressesChunk
     * @param int $startAddress
     * @param int $quantity
     * @param int $unitId
     * @return Request
     */
    abstract protected function createRequest(string $uri, array $addressesChunk, int $startAddress, int $quantity, int $unitId): Request;

    /**
     * @param array<array<string, Address>> $addresses
     * @return Request[]
     */
    public function split(array $addresses): array
    {
        return $this->splitWithUnaddressableRanges($addresses, []);
    }

    /**
     * @param array<array<string, Address>> $addresses
     * @param array<array<Range>> $unaddressableRanges
     * @return Request[]
     */
    public function splitWithUnaddressableRanges(array $addresses, array $unaddressableRanges): array
    {
        $result = [];
        // $modbusPath is uri(server+port)+unitid
        foreach ($addresses as $modbusPath => $addrs) {
            $this->currentUnaddressableRanges = [];
            foreach ($unaddressableRanges as $mbPath => $ranges) {
                if ($modbusPath === $mbPath) {
                    $this->currentUnaddressableRanges = $ranges;
                    break;
                }
            }

            $pathParts = explode(static::UNIT_ID_PREFIX, $modbusPath);
            $uri = $pathParts[0];
            $unitId = (int)$pathParts[1];
            // sort by address and size to help chunking
            // for bytes address type with same address: first byte, second byte
            usort($addrs, function (Address $a, Address $b) {
                $aAddr = $a->getAddress();
                $bAddr = $b->getAddress();
                if ($aAddr === $bAddr) {
                    $sizeCmp = $a->getSize() <=> $b->getSize();
                    if ($sizeCmp !== 0) {
                        return $sizeCmp;
                    }
                    $typeCmp = $a->getType() <=> $b->getType();
                    if ($typeCmp !== 0) {
                        return $typeCmp;
                    }
                    if ($a instanceof ByteReadRegisterAddress && $b instanceof ByteReadRegisterAddress) {
                        return $b->isFirstByte();
                    }
                    return $typeCmp;
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
        if ($currentQuantity >= $this->getMaxAddressesPerModbusRequest()) {
            return true;
        }

        $from = $currentAddress->getAddress();
        $to = $from + $currentAddress->getSize() - 1;

        // we use previous address end as range start because there could be cap between previous and current addresses which could
        // contain also unaddressable addresses
        $previousEnd = $previousAddress !== null ? $previousAddress->getAddress() + $previousAddress->getSize() : -1;
        if (($from - $previousEnd) <= 1) { // directly adjacent or overlapping current does not need checking
            $previousEnd = -1;
        }

        foreach ($this->currentUnaddressableRanges() as $range) {
            if ($range->overlaps($from, $to)) {
                // when currentAddress directly overlaps unaddressable range we have to error out as there is no way to request from that range
                $size = $currentAddress->getSize();
                throw new InvalidArgumentException("address at {$from} with size {$size} overlaps unaddressable range");
            }
            if ($previousEnd != -1 && $range->overlaps($previousEnd, $from - 1)) {
                return true;
            }
        }
        return false;
    }

}
