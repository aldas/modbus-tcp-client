<?php
declare(strict_types=1);

namespace ModbusTcpClient\Network;


use ModbusTcpClient\Composer\Request;
use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ResponseFactory;

class NonBlockingClient
{
    use StreamHandler;

    const OPT_FLAT_REQUEST_RESPONSE = 'flatRequestResponse';
    const OPT_THROW_ON_ERROR = 'throwOnError';

    /**
     * @var array
     */
    private $options = [
        self::OPT_FLAT_REQUEST_RESPONSE => true, // squash sendRequests responses to single array keyed by ... names for read requests
        self::OPT_THROW_ON_ERROR => false
    ];

    public function __construct(array $options = null)
    {
        $this->options = $this->getMergeOptions($options);
    }

    /**
     * Send multiple modbus packets to server and return responses
     *
     * @param ModbusPacket[] $packets
     * @param string|null $uri
     * @param array|null $options
     * @return array
     */
    public function sendPackets(array $packets, string $uri = null, array $options = null)
    {
        $readStreams = [];
        $connections = [];

        $options = $this->getMergeOptions($options);
        $throwOnError = $options[static::OPT_THROW_ON_ERROR] === true;

        if (!empty($uri)) {
            $options['uri'] = mb_strtolower($uri);
        }

        try {
            foreach ($packets as $indexOrKey => $packet) {
                $connection = BinaryStreamConnection::getBuilder()
                    ->setFromOptions($options)
                    ->build();

                $connections[] = $connection->connect();
                $connection->send($packet);

                $readStreams[$indexOrKey] = $connection->getStream();
            }

            $logger = $options['logger'] ?? null;
            $readTimeoutSec = $options['readTimeoutSec'] ?? null;

            $responsePackets = $this->receiveFrom($readStreams, $readTimeoutSec, $logger);

            // extract values and match them old indexes as function argument had to maintain reliable 'order'
            $result = [];
            foreach ($responsePackets as $indexOrKey => $data) {
                $requestPacket = $packets[$indexOrKey];
                $response = ResponseFactory::parseResponse($data);

                if ($throwOnError && $response instanceof ErrorResponse) {
                    throw new ModbusException('sendPackets resulted with modbus error. msg: ' . $response->getErrorMessage());
                }

                $result[$indexOrKey] = $response->withStartAddress($requestPacket->getStartAddress());
            }
        } finally {
            // try to clean up
            foreach ($connections as $connection) {
                $connection->close();
            }
        }
        return $result;
    }

    /**
     * Send single modbus packet to server and return response
     *
     * @param ModbusPacket $packet
     * @param string|null $uri
     * @param array|null $options
     * @return ModbusPacket
     */
    public function sendPacket(ModbusPacket $packet, string $uri = null, array $options = null)
    {
        $responses = $this->sendPackets([$packet], $uri, $options);
        return reset($responses);
    }

    /**
     * Send multiple requests and extract responses
     *
     * @param Request[] $requests
     * @param array|null $options options for tcp stream. See 'BinaryStreamConnection' properties.
     * @return ResultContainer
     */
    public function sendRequests(array $requests, array $options = null): ResultContainer
    {
        $readStreams = [];
        $connections = [];

        $options = $this->getMergeOptions($options);
        $throwOnError = $options[static::OPT_THROW_ON_ERROR] === true;

        try {
            foreach ($requests as $indexOrKey => $request) {
                $uri = mb_strtolower($request->getUri());
                $packet = $request->getRequest();
                $connection = BinaryStreamConnection::getBuilder()
                    ->setFromOptions(array_merge($options, ['uri' => $uri]))
                    ->build();

                $connections[] = $connection->connect();
                $connection->send($packet);

                $readStreams[$indexOrKey] = $connection->getStream();
            }

            $logger = $options['logger'] ?? null;
            $readTimeoutSec = $options['readTimeoutSec'] ?? null;

            $responsePackets = $this->receiveFrom($readStreams, $readTimeoutSec, $logger);

            // extract values and match them old indexes as function argument had to maintain reliable 'order'
            $result = [];
            foreach ($responsePackets as $indexOrKey => $data) {
                $readRequest = $requests[$indexOrKey];
                $response = $readRequest->parse($data);

                if ($throwOnError && $response instanceof ErrorResponse) {
                    throw new ModbusException('sendRequests resulted with modbus error. msg: ' . $response->getErrorMessage());
                }
                $result[$indexOrKey] = $response;
            }
        } finally {
            // try to clean up
            foreach ($connections as $connection) {
                $connection->close();
            }
        }

        return $this->extractErrors($result);
    }

    private function extractErrors(array $results): ResultContainer
    {
        $data = [];
        $errors = [];
        foreach ($results as $index => $result) {
            if ($result instanceof ErrorResponse) {
                $errors[$index] = $result;
            } else {
                $data[$index] = $result;
            }
        }

        if (!empty($data) && $this->options[static::OPT_FLAT_REQUEST_RESPONSE] === true) {
            $data = array_merge(...$data);
        }

        return new ResultContainer($data, $errors);
    }

    /**
     * Send single request and extract response
     *
     * @param Request $request
     * @param array|null $options
     * @return ResultContainer
     */
    public function sendRequest(Request $request, array $options = null): ResultContainer
    {
        return $this->sendRequests([$request], $options);
    }

    private function getMergeOptions(array $options = null)
    {
        if (!empty($options)) {
            return array_merge($this->options, $options);
        }
        return $this->options;
    }
}