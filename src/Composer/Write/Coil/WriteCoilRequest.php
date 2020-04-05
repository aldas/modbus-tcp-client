<?php

namespace ModbusTcpClient\Composer\Write\Coil;


use ModbusTcpClient\Composer\Request;
use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Packet\ModbusRequest;
use ModbusTcpClient\Packet\ModbusResponse;
use ModbusTcpClient\Packet\ResponseFactory;

class WriteCoilRequest implements Request
{
    /**
     * @var string uri to modbus server. Example: 'tcp://192.168.100.1:502'
     */
    private $uri;

    /** @var ModbusRequest */
    private $request;

    /** @var WriteCoilAddress[] */
    private $addresses;


    public function __construct(string $uri, array $addresses, ModbusRequest $request)
    {
        $this->request = $request;
        $this->uri = $uri;
        $this->addresses = $addresses;
    }

    /**
     * @return ModbusRequest
     */
    public function getRequest()
    {
        return $this->request;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return WriteCoilAddress[]
     */
    public function getAddresses(): array
    {
        return $this->addresses;
    }

    public function __toString()
    {
        return $this->request->__toString();
    }

    /**
     * @param string $binaryData
     * @return ModbusResponse
     * @throws ModbusException
     */
    public function parse(string $binaryData): ModbusResponse
    {
        return ResponseFactory::parseResponse($binaryData);
    }
}
