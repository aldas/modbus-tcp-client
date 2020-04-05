<?php

namespace ModbusTcpClient\Composer\Read\Coil;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Packet\ModbusResponse;

class ReadCoilAddress implements Address
{
    /** @var int */
    protected $address;

    /** @var string */
    private $name;

    /** @var callable */
    protected $callback;

    /** @var callable */
    private $errorCallback;

    public function __construct(int $address, string $name = null, callable $callback = null, callable $errorCallback = null)
    {
        $this->address = $address;
        $this->name = $name ?: (string)($address);
        $this->callback = $callback;
        $this->errorCallback = $errorCallback;
    }

    /**
     * @param ModbusResponse $response
     * @return null
     * @throws \Exception
     */
    public function extract(ModbusResponse $response)
    {
        try {
            $result = $response[$this->address];

            if ($this->callback !== null) {
                // callback has access to extracted value, extractor instance that extracted it and whole response
                return ($this->callback)($result, $this, $response);
            }
            return $result;
        } catch (\Exception $exception) {
            if ($this->errorCallback !== null) {
                return ($this->errorCallback)($exception, $this, $response);
            }
            throw $exception;
        }
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getSize(): int
    {
        return 1;
    }

    public function getAddress(): int
    {
        return $this->address;
    }

    public function getType(): string
    {
        return Address::TYPE_BIT;
    }
}
