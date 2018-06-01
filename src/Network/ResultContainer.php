<?php

namespace ModbusTcpClient\Network;


use ArrayIterator;
use ModbusTcpClient\Exception\ModbusException;

class ResultContainer implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @var array
     */
    private $data;
    /**
     * @var array
     */
    private $errors;

    public function __construct(array $data, array $errors)
    {
        $this->data = $data;
        $this->errors = $errors;
    }

    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }

    public function offsetExists($offset)
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset)
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, $value)
    {
        throw new ModbusException('setting value is not supported!');
    }

    public function offsetUnset($offset)
    {
        throw new ModbusException('un-setting value is not supported!');
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}