<?php
declare(strict_types=1);

namespace ModbusTcpClient\Composer\Read\Register;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Composer\RegisterAddress;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersResponse;
use ModbusTcpClient\Utils\Endian;

class ReadRegisterAddress extends RegisterAddress
{
    /** @var string */
    private string $name;

    /** @var callable */
    protected $callback;

    /** @var callable */
    private $errorCallback;

    /** @var int */
    private int $endian;

    public function __construct(
        int      $address,
        string   $type,
        string   $name = null,
        callable $callback = null,
        callable $errorCallback = null,
        int      $endian = null
    )
    {
        parent::__construct($address, $type);

        $this->endian = $endian === null ? Endian::$defaultEndian : $endian;
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
            Address::TYPE_DOUBLE,
        ];
    }

    protected function extractInternal(ReadHoldingRegistersResponse|ReadInputRegistersRequest $response): mixed
    {
        $result = null;
        switch ($this->type) {
            case Address::TYPE_INT16:
                $result = $response->getWordAt($this->address)->getInt16($this->endian);
                break;
            case Address::TYPE_UINT16:
                $result = $response->getWordAt($this->address)->getUInt16($this->endian);
                break;
            case Address::TYPE_INT32:
                $result = $response->getDoubleWordAt($this->address)->getInt32($this->endian);
                break;
            case Address::TYPE_UINT32:
                $result = $response->getDoubleWordAt($this->address)->getUInt32($this->endian);
                break;
            case Address::TYPE_FLOAT:
                $result = $response->getDoubleWordAt($this->address)->getFloat($this->endian);
                break;
            case Address::TYPE_DOUBLE:
                $result = $response->getQuadWordAt($this->address)->getDouble($this->endian);
                break;
            case Address::TYPE_INT64:
                $result = $response->getQuadWordAt($this->address)->getInt64($this->endian);
                break;
            case Address::TYPE_UINT64:
                $result = $response->getQuadWordAt($this->address)->getUInt64($this->endian);
                break;
        }
        return $result;
    }

    /**
     * @param ReadHoldingRegistersResponse|ReadInputRegistersResponse $response
     * @return mixed
     * @throws \Exception
     */
    public function extract(ReadHoldingRegistersResponse|ReadInputRegistersResponse $response): mixed
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

    public function getEndian(): int
    {
        return $this->endian;
    }
}
