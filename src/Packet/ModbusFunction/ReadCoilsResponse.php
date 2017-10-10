<?php

namespace ModbusTcpClient\Packet\ModbusFunction;

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

    public function __construct($rawData, $unitId = 0, $transactionId = null)
    {
        $data = substr($rawData, 1);
        $this->coilsBytesLength = strlen($data);
        $this->coils = Types::binaryStringToBooleanArray($data);

        parent::__construct($rawData, $unitId, $transactionId);
    }

    public function getFunctionCode()
    {
        return ModbusPacket::READ_COILS;
    }

    public function getCoils()
    {
        return iterator_to_array($this->getIterator());
    }

    public function __toString()
    {
        return parent::__toString()
            . Types::byteArrayToByte(Types::booleanArrayToByteArray($this->coils));
    }

    protected function getLengthInternal()
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
     * @throws \LogicException
     */
    public function offsetSet($offset, $value)
    {
        throw new \LogicException('setting value in response is not supported!');
    }

    /**
     * @param $offset
     * @throws \LogicException
     */
    public function offsetUnset($offset)
    {
        throw new \LogicException('unsetting value in response is not supported!');
    }

    public function offsetExists($offset)
    {
        return isset($this->coils[$offset - $this->getStartAddress()]);
    }

    public function offsetGet($offset)
    {
        $address = ($offset - $this->getStartAddress()) * 2;
        $byteCount = $this->getByteCount();
        if ($address < 0 || $address >= $byteCount) {
            throw new \OutOfBoundsException('offset out of bounds');
        }
        return $this->coils[$offset - $this->getStartAddress()];
    }
}