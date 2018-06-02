<?php

namespace ModbusTcpClient\Composer\Read;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Packet\ModbusResponse;

class ReadAddress extends Address
{
    /** @var string */
    private $name;

    /** @var callable */
    protected $callback;

    /** @var callable */
    private $errorCallback;

    public function __construct(int $address, string $type, string $name = null, callable $callback = null, callable $errorCallback = null)
    {
        parent::__construct($address, $type);

        $this->name = $name ?: "{$type}_{$address}";
        $this->callback = $callback;
        $this->errorCallback = $errorCallback;
    }

    protected function getAllowedTypes(): array
    {
        return [
            Address::TYPE_INT16,
            Address::TYPE_UINT16,
            Address::TYPE_INT32,
            Address::TYPE_UINT32,
            Address::TYPE_INT64,
            Address::TYPE_UINT64,
            Address::TYPE_FLOAT,
        ];
    }

    protected function extractInternal(ModbusResponse $response)
    {
        $result = null;
        switch ($this->type) {
            case ReadAddress::TYPE_INT16:
                $result = $response->getWordAt($this->address)->getInt16();
                break;
            case ReadAddress::TYPE_UINT16:
                $result = $response->getWordAt($this->address)->getUInt16();
                break;
            case ReadAddress::TYPE_INT32:
                $result = $response->getDoubleWordAt($this->address)->getInt32();
                break;
            case ReadAddress::TYPE_UINT32:
                $result = $response->getDoubleWordAt($this->address)->getUInt32();
                break;
            case ReadAddress::TYPE_FLOAT:
                $result = $response->getDoubleWordAt($this->address)->getFloat();
                break;
            case ReadAddress::TYPE_INT64:
                $result = $response->getQuadWordAt($this->address)->getInt64();
                break;
            case ReadAddress::TYPE_UINT64:
                $result = $response->getQuadWordAt($this->address)->getUInt64();
                break;
        }
        return $result;
    }

    /**
     * @param ModbusResponse $response
     * @return null
     * @throws \Exception
     */
    public function extract(ModbusResponse $response)
    {
        try {
            $result = $this->extractInternal($response);

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
}