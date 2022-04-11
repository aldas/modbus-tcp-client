<?php
declare(strict_types=1);

namespace ModbusTcpClient\Composer\Read\Register;


use ModbusTcpClient\Composer\Request;
use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Packet\ResponseFactory;

class ReadRegisterRequest implements Request
{
    /**
     * @var string uri to modbus server. Example: 'tcp://192.168.100.1:502'
     */
    private string $uri;

    /** @var ReadRegisterAddress[] */
    private array $addresses;

    /** @var ReadHoldingRegistersRequest */
    private ReadHoldingRegistersRequest $request;


    /**
     * @param string $uri
     * @param ReadRegisterAddress[] $addresses
     * @param ReadHoldingRegistersRequest $request
     */
    public function __construct(string $uri, array $addresses, ReadHoldingRegistersRequest $request)
    {
        $this->addresses = $addresses;
        $this->request = $request;
        $this->uri = $uri;
    }

    /**
     * @return ReadHoldingRegistersRequest
     */
    public function getRequest(): ReadHoldingRegistersRequest
    {
        return $this->request;
    }

    public function getUri(): string
    {
        return $this->uri;
    }

    /**
     * @return ReadRegisterAddress[]
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
     * @throws \Exception
     */
    public function parse(string $binaryData): array|ErrorResponse
    {
        $response = ResponseFactory::parseResponse($binaryData)->withStartAddress($this->request->getStartAddress());
        if ($response instanceof ErrorResponse) {
            return $response;
        }
        if (!$response instanceof ReadHoldingRegistersResponse) {
            throw new InvalidArgumentException("given data is not valid modbus response");
        }

        $result = [];
        foreach ($this->addresses as $address) {
            $result[$address->getName()] = $address->extract($response);
        }
        return $result;
    }
}
