<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Packet\ByteCountResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Types;

/**
 * Response for Read Coils (FC=01)
 *
 * Example packet: \x81\x80\x00\x00\x00\x05\x03\x01\x02\xCD\x6B
 * \x81\x80 - transaction id
 * \x00\x00 - protocol id
 * \x00\x05 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x03 - unit id
 * \x01 - function code
 * \x02 - coils byte count
 * \xCD\x6B - coils data (2 bytes = 2 * 8 coils)
 *
 * @implements \IteratorAggregate<int, bool>
 * @implements \ArrayAccess<int, bool>
 */
class ReadCoilsResponse extends ByteCountResponse implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @var bool[]
     */
    private array $coils;

    /**
     * @var int
     */
    private int $coilsBytesLength;

    public function __construct(string $rawData, int $unitId = 0, int $transactionId = null)
    {
        $data = substr($rawData, 1);
        $this->coilsBytesLength = strlen($data);
        $this->coils = Types::binaryStringToBooleanArray($data);

        parent::__construct($rawData, $unitId, $transactionId);
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::READ_COILS;
    }

    /**
     * @return bool[]
     */
    public function getCoils(): array
    {
        return iterator_to_array($this->getIterator());
    }

    public function __toString(): string
    {
        return parent::__toString()
            . Types::byteArrayToByte(Types::booleanArrayToByteArray($this->coils));
    }

    protected function getLengthInternal(): int
    {
        return parent::getLengthInternal() + $this->coilsBytesLength;
    }

    /**
     * @return \Generator<bool>
     */
    public function getIterator(): \Generator
    {
        $index = $this->getStartAddress();
        foreach ($this->coils as $coil) {
            yield $index++ => $coil;
        }
    }

    /**
     * @param int $offset
     * @param bool $value
     * @throws ModbusException
     */
    public function offsetSet($offset, $value): void
    {
        throw new ModbusException('setting value in response is not supported!');
    }

    /**
     * @param int $offset
     * @throws ModbusException
     */
    public function offsetUnset($offset): void
    {
        throw new ModbusException('unsetting value in response is not supported!');
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetExists($offset): bool
    {
        return isset($this->coils[$offset - $this->getStartAddress()]);
    }

    /**
     * @param int $offset
     * @return bool
     */
    public function offsetGet($offset): bool
    {
        $address = $offset - $this->getStartAddress();
        $endAddress = ($this->getByteCount() * 8);
        if ($address < 0 || $address >= $endAddress) {
            throw new InvalidArgumentException('offset out of bounds');
        }
        return $this->coils[$offset - $this->getStartAddress()];
    }
}
