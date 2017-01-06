<?php

namespace ModbusTcpClient\Network;


use Psr\Log\LoggerInterface;

class BinaryStreamConnectionBuilder extends BinaryStreamConnectionProperties
{
    /**
     * Return built instance of BinaryStreamConnection
     *
     * @return BinaryStreamConnection built instance
     * @throws \LogicException
     */
    public function build()
    {
        if (empty($this->host)) {
            throw new \LogicException('host property can not be left null or empty!');
        }
        return new BinaryStreamConnection($this);
    }

    /**
     * @param string $client
     * @return BinaryStreamConnectionBuilder
     */
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @param string $clientPort
     * @return BinaryStreamConnectionBuilder
     */
    public function setClientPort($clientPort)
    {
        $this->clientPort = $clientPort;
        return $this;
    }

    /**
     * @param float $timeoutSec
     * @return BinaryStreamConnectionBuilder
     */
    public function setTimeoutSec($timeoutSec)
    {
        $this->timeoutSec = $timeoutSec;
        return $this;
    }

    /**
     * @param float $connectTimeoutSec
     * @return BinaryStreamConnectionBuilder
     */
    public function setConnectTimeoutSec($connectTimeoutSec)
    {
        $this->connectTimeoutSec = $connectTimeoutSec;
        return $this;
    }

    /**
     * @param float $readTimeoutSec
     * @return BinaryStreamConnectionBuilder
     */
    public function setReadTimeoutSec($readTimeoutSec)
    {
        $this->readTimeoutSec = $readTimeoutSec;
        return $this;
    }

    /**
     * @param float $writeTimeoutSec
     * @return BinaryStreamConnectionBuilder
     */
    public function setWriteTimeoutSec($writeTimeoutSec)
    {
        $this->writeTimeoutSec = $writeTimeoutSec;
        return $this;
    }

    /**
     * @param string $protocol
     * @return BinaryStreamConnectionBuilder
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
        return $this;
    }

    /**
     * @param string $host
     * @return BinaryStreamConnectionBuilder
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param string $port
     * @return BinaryStreamConnectionBuilder
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @return BinaryStreamConnectionBuilder
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

}
