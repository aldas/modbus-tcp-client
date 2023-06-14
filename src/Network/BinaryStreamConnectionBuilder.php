<?php
declare(strict_types=1);

namespace ModbusTcpClient\Network;


use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Utils\Packet;
use Psr\Log\LoggerInterface;

class BinaryStreamConnectionBuilder extends BinaryStreamConnectionProperties
{
    /**
     * Return built instance of BinaryStreamConnection
     *
     * @return BinaryStreamConnection built instance
     * @throws InvalidArgumentException
     */
    public function build(): BinaryStreamConnection
    {
        if ($this->host === null && $this->uri === null) {
            throw new InvalidArgumentException('host or uri property can not be left null or empty!');
        }

        if ($this->createStreamCallback === null) {
            if ($this->protocol === StreamCreator::TYPE_SERIAL) {
                $streamCreator = new SerialStreamCreator();
            } else {
                $streamCreator = new InternetDomainStreamCreator();
            }

            $this->createStreamCallback = function (BinaryStreamConnection $conn) use ($streamCreator) {
                return $streamCreator->createStream($conn);
            };
        }
        if ($this->isCompleteCallback === null) {
            $this->isCompleteCallback = static function ($binaryData, $streamIndex) {
                return Packet::isCompleteLength($binaryData);
            };
        }

        return new BinaryStreamConnection($this);
    }

    /**
     * @param string $client
     * @return static
     */
    public function setClient(string $client): static
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @param int $clientPort
     * @return static
     */
    public function setClientPort(int $clientPort): static
    {
        $this->clientPort = $clientPort;
        return $this;
    }

    /**
     * @param float $timeoutSec
     * @return static
     */
    public function setTimeoutSec(float $timeoutSec): static
    {
        $this->timeoutSec = $timeoutSec;
        return $this;
    }

    /**
     * @param float $connectTimeoutSec
     * @return static
     */
    public function setConnectTimeoutSec(float $connectTimeoutSec): static
    {
        $this->connectTimeoutSec = $connectTimeoutSec;
        return $this;
    }

    /**
     * @param float $readTimeoutSec
     * @return static
     */
    public function setReadTimeoutSec(float $readTimeoutSec): static
    {
        $this->readTimeoutSec = $readTimeoutSec;
        return $this;
    }

    /**
     * @param float $writeTimeoutSec
     * @return static
     */
    public function setWriteTimeoutSec(float $writeTimeoutSec): static
    {
        $this->writeTimeoutSec = $writeTimeoutSec;
        return $this;
    }

    /**
     * @param int $delayReadMicroSec delay before read in done (microseconds). This is useful for (USB) Serial
     * devices that need time between writing request to the device and reading the response from device.
     * @return static
     */
    public function setDelayRead(int $delayReadMicroSec): static
    {
        $this->delayRead = $delayReadMicroSec;
        return $this;
    }

    /**
     * @param string $protocol
     * @return static
     */
    public function setProtocol(string $protocol): static
    {
        $this->protocol = $protocol;
        return $this;
    }

    /**
     * @param string $host
     * @return static
     */
    public function setHost(string $host): static
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param int $port
     * @return static
     */
    public function setPort(int $port): static
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @return static
     */
    public function setLogger(LoggerInterface $logger): static
    {
        $this->logger = $logger;
        return $this;
    }

    /**
     * @param string $uri
     * @return static
     */
    public function setUri(string $uri): static
    {
        $this->uri = $uri;
        return $this;
    }

    /**
     * @param array<string,mixed>|null $options
     * @return $this
     */
    public function setFromOptions(array $options = null): static
    {
        if ($options !== null) {
            foreach ($options as $option => $value) {
                if (property_exists($this, $option)) {
                    $this->{$option} = $value;
                }
            }
        }

        return $this;
    }

    /**
     * @param callable $createStreamCallback callable to create stream
     * @return static
     */
    public function setCreateStreamCallback(callable $createStreamCallback): static
    {
        $this->createStreamCallback = $createStreamCallback;
        return $this;
    }

    /**
     * @param callable $isCompleteCallback callable to check if data received from stream is complete/all that is needed
     * @return static
     */
    public function setIsCompleteCallback(callable $isCompleteCallback): static
    {
        $this->isCompleteCallback = $isCompleteCallback;
        return $this;
    }

}
