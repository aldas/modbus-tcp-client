<?php

namespace ModbusTcpClient\Network;


use Psr\Log\LoggerInterface;

class ModbusConnectionBuilder extends ModbusConnectionProperties
{
    /**
     * Return built instance of ModbusConnection
     *
     * @return ModbusConnection built instance
     * @throws \LogicException
     */
    public function build()
    {
        if (empty($this->host)) {
            throw new \LogicException('host property can not be left null or empty!');
        }
        return new ModbusConnection($this);
    }

    /**
     * @param string $client
     * @return ModbusConnectionBuilder
     */
    public function setClient($client)
    {
        $this->client = $client;
        return $this;
    }

    /**
     * @param string $clientPort
     * @return ModbusConnectionBuilder
     */
    public function setClientPort($clientPort)
    {
        $this->clientPort = $clientPort;
        return $this;
    }

    /**
     * @param float $timeoutSec
     * @return ModbusConnectionBuilder
     */
    public function setTimeoutSec($timeoutSec)
    {
        $this->timeoutSec = $timeoutSec;
        return $this;
    }

    /**
     * @param float $connectTimeoutSec
     * @return ModbusConnectionBuilder
     */
    public function setConnectTimeoutSec($connectTimeoutSec)
    {
        $this->connectTimeoutSec = $connectTimeoutSec;
        return $this;
    }

    /**
     * @param float $readTimeoutSec
     * @return ModbusConnectionBuilder
     */
    public function setReadTimeoutSec($readTimeoutSec)
    {
        $this->readTimeoutSec = $readTimeoutSec;
        return $this;
    }

    /**
     * @param float $writeTimeoutSec
     * @return ModbusConnectionBuilder
     */
    public function setWriteTimeoutSec($writeTimeoutSec)
    {
        $this->writeTimeoutSec = $writeTimeoutSec;
        return $this;
    }

    /**
     * @param string $protocol
     * @return ModbusConnectionBuilder
     */
    public function setProtocol($protocol)
    {
        $this->protocol = $protocol;
        return $this;
    }

    /**
     * @param string $host
     * @return ModbusConnectionBuilder
     */
    public function setHost($host)
    {
        $this->host = $host;
        return $this;
    }

    /**
     * @param string $port
     * @return ModbusConnectionBuilder
     */
    public function setPort($port)
    {
        $this->port = $port;
        return $this;
    }

    /**
     * @param LoggerInterface $logger
     * @return ModbusConnectionBuilder
     */
    public function setLogger($logger)
    {
        $this->logger = $logger;
        return $this;
    }

}
