<?php
declare(strict_types=1);

namespace ModbusTcpClient\Composer\Read\Coil;


use ModbusTcpClient\Composer\Request;
use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsRequest;
use ModbusTcpClient\Packet\ResponseFactory;

class ReadCoilRequest implements Request
{
    /**
     * @var string uri to modbus server. Example: 'tcp://192.168.100.1:502'
     */
    private string $uri;

    /** @var ReadCoilAddress[] */
    private array $addresses;

    /** @var ReadCoilsRequest */
    private ReadCoilsRequest $request;


    /**
     * @param string $uri
     * @param ReadCoilAddress[] $addresses
     * @param ReadCoilsRequest $request
     */
    public function __construct(string $uri, array $addresses, ReadCoilsRequest $request)
    {
        $this->addresses = $addresses;
        $this->request = $request;
        $this->uri = $uri;
    }

    /**
     * @return ReadCoilsRequest
     */
    public function getRequest(): ReadCoilsRequest
    {
        return $this->request;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return ReadCoilAddress[]
     */
    public function getAddresses(): array
    {
        return $this->addresses;
    }

    public function __toString(): string
    {
        return $this->request->__toString();
    }

    /**
     * @param string $binaryData
     * @return array<string,mixed>|ErrorResponse
     * @throws ModbusException
     * @throws \Exception
     */
    public function parse(string $binaryData): array|ErrorResponse
    {
        $response = ResponseFactory::parseResponse($binaryData)->withStartAddress($this->request->getStartAddress());
        if ($response instanceof ErrorResponse) {
            return $response;
        }

        $result = [];
        foreach ($this->addresses as $address) {
            $result[$address->getName()] = $address->extract($response);
        }
        return $result;
    }
}
