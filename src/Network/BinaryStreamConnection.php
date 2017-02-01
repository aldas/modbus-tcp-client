<?php
namespace ModbusTcpClient\Network;

use InvalidArgumentException;

class BinaryStreamConnection extends BinaryStreamConnectionProperties
{
    /**
     * @var resource communication socket
     */
    private $streamSocket;

    public function __construct(BinaryStreamConnectionBuilder $builder)
    {
        $this->host = $builder->getHost();
        $this->port = $builder->getPort();
        $this->client = $builder->getClient();
        $this->clientPort = $builder->getClientPort();
        $this->timeoutSec = $builder->getTimeoutSec();
        $this->connectTimeoutSec = $builder->getConnectTimeoutSec();
        $this->readTimeoutSec = $builder->getReadTimeoutSec();
        $this->writeTimeoutSec = $builder->getWriteTimeoutSec();
        $this->protocol = $builder->getProtocol();
        $this->logger = $builder->getLogger();
    }

    public static function getBuilder()
    {
        return new BinaryStreamConnectionBuilder();
    }

    public function connect()
    {
        $protocol = strtolower($this->getProtocol());
        if (!($protocol === 'tcp' || $protocol === 'udp')) {
            throw new InvalidArgumentException("Unknown protocol, should be 'TCP' or 'UDP'");
        }

        $opts = [];
        if (strlen($this->getClient()) > 0) {
            // Bind the client stream to a specific local network interface and port
            $opts = [
                'socket' => [
                    'bindto' => "{$this->getClient()}:{$this->getClientPort()}",
                ],
            ];
        }
        $context = stream_context_create($opts);

        $this->streamSocket = @stream_socket_client(
            "$protocol://$this->host:$this->port",
            $errno,
            $errstr,
            $this->connectTimeoutSec,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (false === $this->streamSocket) {
            $message = "Unable to create client socket to {$protocol}://{$this->host}:{$this->port}: {$errstr}";
            throw new IOException($message, $errno);
        }

        if ($this->logger) {
            $this->logger->debug('Connected');
        }

        stream_set_blocking($this->streamSocket, false); // use non-blocking stream

        // set as stream timeout as we use 'stream_select' to read data and this method has its own timeout
        // this call will only affect our fwrite parts (send data method)
        stream_set_timeout(
            $this->streamSocket,
            (int)$this->getWriteTimeoutSec(),
            $this->extractUsec($this->getWriteTimeoutSec())
        );

        return $this;
    }

    public function receive()
    {
        $lastAccess = microtime(true);

        while (true) {
            $read = [$this->streamSocket];
            $write = null;
            $except = null;
            $data = '';
            if (false !== stream_select(
                    $read,
                    $write,
                    $except,
                    (int)$this->getReadTimeoutSec(),
                    $this->extractUsec($this->getReadTimeoutSec())
                )
            ) {
                if ($this->logger) {
                    $this->logger->debug('Polling data');
                }

                if (in_array($this->streamSocket, $read, false)) {
                    $data .= fread($this->streamSocket, 2048); // read max 2048 bytes
                    if (!empty($data)) {
                        if ($this->logger) {
                            $this->logger->debug('Data received: ' . unpack('H*', $data));
                        }
                        return $data;
                    }
                    $lastAccess = microtime(true);
                } else {
                    $timeSpentWaiting = microtime(true) - $lastAccess;
                    if ($timeSpentWaiting >= $this->getTimeoutSec()) {
                        throw new IOException('Read total timeout expired');
                    }
                }
            } else {
                throw new IOException("Failed to read data from {$this->host}:{$this->port}.");
            }
        }
        return null;
    }

    public function send($packet)
    {
        fwrite($this->streamSocket, $packet, strlen($packet));

        if ($this->logger) {
            $this->logger->debug('Data sent: ' . unpack('H*', $packet));
        }

        return $this;
    }

    public function sendAndReceive($packet)
    {
        return $this->send($packet)->receive();
    }

    public function close()
    {
        if (is_resource($this->streamSocket)) {
            fclose($this->streamSocket);
        }
    }

    public function __destruct()
    {
        $this->close();
    }

    /**
     * @param float $seconds
     * @return int
     */
    private function extractUsec($seconds)
    {
        return (int)(($seconds - (int)$seconds) * 1e6);
    }
}