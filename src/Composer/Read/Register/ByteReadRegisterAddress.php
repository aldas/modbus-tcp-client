<?php
declare(strict_types=1);

namespace ModbusTcpClient\Composer\Read\Register;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersRequest;

class ByteReadRegisterAddress extends ReadRegisterAddress
{
    /** @var bool */
    private bool $firstByte;

    public function __construct(int $address, bool $firstByte, string $name = null, callable $callback = null, callable $errorCallback = null)
    {
        $type = Address::TYPE_BYTE;
        $fbInt = (int)$firstByte;
        parent::__construct($address, $type, $name ?: "{$type}_{$address}_{$fbInt}", $callback, $errorCallback);
        $this->firstByte = $firstByte;
    }

    protected function extractInternal(ReadHoldingRegistersResponse|ReadInputRegistersRequest $response): mixed
    {
        $word = $response->getWordAt($this->address);
        return $this->firstByte ? $word->getLowByteAsInt() : $word->getHighByteAsInt();
    }

    /**
     * @return bool
     */
    public function isFirstByte(): bool
    {
        return $this->firstByte;
    }

    protected function getAllowedTypes(): array
    {
        return [Address::TYPE_BYTE];
    }
}
