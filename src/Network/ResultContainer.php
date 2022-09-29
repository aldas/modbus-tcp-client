<?php
declare(strict_types=1);

namespace ModbusTcpClient\Network;


use ArrayIterator;
use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Packet\ErrorResponse;

/**
 * @implements \IteratorAggregate<string, mixed>
 * @implements \ArrayAccess<string, mixed>
 */
class ResultContainer implements \ArrayAccess, \IteratorAggregate
{
    /**
     * @var array<string, mixed>
     */
    private array $data;
    /**
     * @var ErrorResponse[]
     */
    private array $errors;

    /**
     * @param array<string, mixed> $data
     * @param ErrorResponse[] $errors
     */
    public function __construct(array $data, array $errors)
    {
        $this->data = $data;
        $this->errors = $errors;
    }

    /**
     * @return ArrayIterator<string, mixed>
     */
    public function getIterator(): ArrayIterator
    {
        return new ArrayIterator($this->data);
    }

    public function offsetExists($offset): bool
    {
        return isset($this->data[$offset]);
    }

    public function offsetGet($offset): mixed
    {
        return $this->data[$offset] ?? null;
    }

    public function offsetSet($offset, $value): void
    {
        throw new ModbusException('setting value is not supported!');
    }

    public function offsetUnset($offset): void
    {
        throw new ModbusException('un-setting value is not supported!');
    }

    /**
     * @return array<string, mixed>
     */
    public function getData(): array
    {
        return $this->data;
    }

    /**
     * @return ErrorResponse[]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
