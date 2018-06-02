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
 */
class ReadCoilsResponse extends ByteCountResponse implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @var array
     */
    private $coils;

    /**
     * @var int
     */
    private $coilsBytesLength;

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

    public function getIterator()
    {
        $index = $this->getStartAddress();
        foreach ($this->coils as $coil) {
            yield $index++ => $coil;
        }
    }

    /**
     * @param $offset
     * @param $value
     * @throws ModbusException
     */
    public function offsetSet($offset, $value)
    {
        throw new ModbusException('setting value in response is not supported!');
    }

    /**
     * @param $offset
     * @throws ModbusException
     */
    public function offsetUnset($offset)
    {
        throw new ModbusException('unsetting value in response is not supported!');
    }

    public function offsetExists($offset)
    {
        return isset($this->coils[$offset - $this->getStartAddress()]);
    }

    public function offsetGet($offset)
    {
        $address = $offset - $this->getStartAddress();
        $endAddress = ($this->getByteCount() * 8);
        if ($address < 0 || $address >= $endAddress) {
            throw new InvalidArgumentException('offset out of bounds');
        }
        return $this->coils[$offset - $this->getStartAddress()];
    }
}