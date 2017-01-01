<?php
namespace ModbusTcpClient\Network;

use Psr\Log\LoggerInterface;

/**
 * ModbusConnection immutable properties base class
 */
abstract class ModbusConnectionProperties
{
    /**
     * @var string (optional) client IP address when binding client
     */
    protected $client = null;
    /**
     * @var string client port set when binding client to local ip&port
     */
    protected $clientPort = 502;
    /**
     * @var float Total response timeout (seconds, decimals allowed)
     */
    protected $timeoutSec = 5;
    /**
     * @var float maximum timeout when establishing connection (seconds, decimals allowed)
     */
    protected $connectTimeoutSec = 1;
    /**
     * @var float read timeout (seconds, decimals allowed)
     */
    protected $readTimeoutSec = 0.3;
    /**
     * @var float maximum timeout for write operation on connection (seconds, decimals allowed)
     */
    protected $writeTimeoutSec = 1;
    /**
     * @var string network protocol (TCP, UDP)
     */
    protected $protocol = 'TCP';
    /**
     * @var string|null Modbus device IP address
     */
    protected $host;
    /**
     * @var string gateway port
     */
    protected $port = 502;

    /**
     * @var LoggerInterface
     */
    protected $logger;



    /**
     * @return string (optional) client IP address when binding client
     */
    public function getClient()
    {
        return $this->client;
    }

    /**
     * @return string client port set when binding client to local ip&port
     */
    public function getClientPort()
    {
        return $this->clientPort;
    }

    /**
     * @return float Total response timeout (seconds, decimals allowed)
     */
    public function getTimeoutSec()
    {
        return $this->timeoutSec;
    }

    /**
     * @return float maximum timeout when establishing connection (seconds, decimals allowed)
     */
    public function getConnectTimeoutSec()
    {
        return $this->connectTimeoutSec;
    }

    /**
     * @return float read timeout (seconds, decimals allowed)
     */
    public function getReadTimeoutSec()
    {
        return $this->readTimeoutSec;
    }

    /**
     * @return float maximum timeout for write operation on connection (seconds, decimals allowed)
     */
    public function getWriteTimeoutSec()
    {
        return $this->writeTimeoutSec;
    }

    /**
     * @return string network protocol (TCP, UDP)
     */
    public function getProtocol()
    {
        return $this->protocol;
    }

    /**
     * @return string Modbus device IP address
     */
    public function getHost()
    {
        return $this->host;
    }

    /**
     * @return string gateway port
     */
    public function getPort()
    {
        return $this->port;
    }

    /**
     * @return LoggerInterface
     */
    public function getLogger()
    {
        return $this->logger;
    }

}