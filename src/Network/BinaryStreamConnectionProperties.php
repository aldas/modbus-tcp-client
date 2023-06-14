<?php
declare(strict_types=1);

namespace ModbusTcpClient\Network;

use Psr\Log\LoggerInterface;

/**
 * BinaryStreamConnection immutable properties base class
 *
 * uses same properties as PhpModbus library ModbusMaster class
 */
abstract class BinaryStreamConnectionProperties
{
    /**
     * @var string|null (optional) client IP address when binding client
     */
    protected ?string $client = null;
    /**
     * @var int client port set when binding client to local ip&port
     */
    protected int $clientPort = 502;
    /**
     * @var float Total response timeout (seconds, decimals allowed)
     */
    protected float $timeoutSec = 5;
    /**
     * @var float maximum timeout when establishing connection (seconds, decimals allowed)
     */
    protected float $connectTimeoutSec = 1;
    /**
     * @var float read timeout (seconds, decimals allowed)
     */
    protected float $readTimeoutSec = 0.3;
    /**
     * @var float maximum timeout for write operation on connection (seconds, decimals allowed)
     */
    protected float $writeTimeoutSec = 1;

    /**
     * @var int delay before read in done (microseconds). This is useful for (USB) Serial devices that need time between
     * writing the request to the device and reading the response from device.
     */
    protected int $delayRead = 0;

    /**
     * @var string|null uri to connect to. Has higher priority than $protocol/$host/$port. Example: 'tcp://192.168.0.1:502'
     */
    protected ?string $uri = null;
    /**
     * @var string network protocol (TCP, UDP)
     */
    protected string $protocol = StreamCreator::TYPE_TCP;
    /**
     * @var string|null Modbus device IP address
     */
    protected ?string $host = null;
    /**
     * @var int gateway port
     */
    protected int $port = 502;

    /**
     * @var LoggerInterface|null
     */
    protected ?LoggerInterface $logger = null;

    /**
     * @var callable callable to create stream
     */
    protected $createStreamCallback;

    /**
     * @var callable callable to check if data received from stream is complete/all that is needed
     */
    protected $isCompleteCallback;

    /**
     * @return string|null (optional) client IP address when binding client
     */
    public function getClient(): ?string
    {
        return $this->client;
    }

    /**
     * @return int client port set when binding client to local ip&port
     */
    public function getClientPort(): int
    {
        return $this->clientPort;
    }

    /**
     * @return float Total response timeout (seconds, decimals allowed)
     */
    public function getTimeoutSec(): float
    {
        return $this->timeoutSec;
    }

    /**
     * @return float maximum timeout when establishing connection (seconds, decimals allowed)
     */
    public function getConnectTimeoutSec(): float
    {
        return $this->connectTimeoutSec;
    }

    /**
     * @return float read timeout (seconds, decimals allowed)
     */
    public function getReadTimeoutSec(): float
    {
        return $this->readTimeoutSec;
    }

    /**
     * @return float maximum timeout for write operation on connection (seconds, decimals allowed)
     */
    public function getWriteTimeoutSec(): float
    {
        return $this->writeTimeoutSec;
    }

    /**
     * @return string network protocol (TCP, UDP)
     */
    public function getProtocol(): string
    {
        return $this->protocol;
    }

    /**
     * @return string|null Modbus device IP address
     */
    public function getHost(): ?string
    {
        return $this->host;
    }

    /**
     * @return int gateway port
     */
    public function getPort(): int
    {
        return $this->port;
    }

    /**
     * @return LoggerInterface|null
     */
    public function getLogger(): LoggerInterface|null
    {
        return $this->logger;
    }

    /**
     * @return string|null
     */
    public function getUri(): ?string
    {
        return $this->uri;
    }

    /**
     * @return callable
     */
    public function getCreateStreamCallback(): callable
    {
        return $this->createStreamCallback;
    }

    /**
     * @return int
     */
    public function getDelayRead(): int
    {
        return $this->delayRead;
    }

    /**
     * @return callable
     */
    protected function getIsCompleteCallback(): callable
    {
        return $this->isCompleteCallback;
    }
}
