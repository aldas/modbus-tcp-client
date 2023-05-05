<?php
declare(strict_types=1);

namespace ModbusTcpClient\Composer\Write\Coil;


use ModbusTcpClient\Composer\Request;
use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleCoilsResponse;
use ModbusTcpClient\Packet\ModbusRequest;
use ModbusTcpClient\Packet\ResponseFactory;

class WriteCoilRequest implements Request
{
    /**
     * @var string uri to modbus server. Example: 'tcp://192.168.100.1:502'
     */
    private string $uri;

    /** @var ModbusRequest */
    private ModbusRequest $request;

    /** @var WriteCoilAddress[] */
    private array $addresses;


    /**
     * @param string $uri
     * @param WriteCoilAddress[] $addresses
     * @param ModbusRequest $request
     */
    public function __construct(string $uri, array $addresses, ModbusRequest $request)
    {
        $this->request = $request;
        $this->uri = $uri;
        $this->addresses = $addresses;
    }

    /**
     * @return ModbusRequest
     */
    public function getRequest(): ModbusRequest
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
     * @return array<string, mixed>|ErrorResponse
     * @throws ModbusException
     */
    public function parse(string $binaryData): array|ErrorResponse
    {
        $response = ResponseFactory::parseResponse($binaryData);
        if ($response instanceof ErrorResponse) {
            return $response;
        }
        if (!$response instanceof WriteMultipleCoilsResponse) {
            throw new InvalidArgumentException("given data is not valid modbus response");
        }
        return []; // write requests do not return any data
    }
}
