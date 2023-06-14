<?php
declare(strict_types=1);

namespace ModbusTcpClient\Network;

use ModbusTcpClient\Packet\ModbusPacket;

class BinaryStreamConnection extends BinaryStreamConnectionProperties
{
    use StreamHandler;

    /**
     * @var resource|null communication stream
     */
    private $stream;

    public function __construct(BinaryStreamConnectionBuilder $builder)
    {
        $this->uri = $builder->getUri();
        $this->host = $builder->getHost();
        $this->port = $builder->getPort();
        $this->client = $builder->getClient();
        $this->clientPort = $builder->getClientPort();
        $this->timeoutSec = $builder->getTimeoutSec();
        $this->connectTimeoutSec = $builder->getConnectTimeoutSec();
        $this->readTimeoutSec = $builder->getReadTimeoutSec();
        $this->writeTimeoutSec = $builder->getWriteTimeoutSec();
        $this->delayRead = $builder->getDelayRead();
        $this->protocol = $builder->getProtocol();
        $this->logger = $builder->getLogger();
        $this->createStreamCallback = $builder->getCreateStreamCallback();
        $this->isCompleteCallback = $builder->getIsCompleteCallback();
    }

    public static function getBuilder(): BinaryStreamConnectionBuilder
    {
        return new BinaryStreamConnectionBuilder();
    }

    public function connect(): BinaryStreamConnection
    {
        $this->stream = ($this->createStreamCallback)($this);

        $this->logger?->debug('Connected');

        stream_set_blocking($this->stream, false); // use non-blocking stream

        // set as stream timeout as we use 'stream_select' to read data and this method has its own timeout
        // this call will only affect our fwrite parts (send data method)
        stream_set_timeout(
            $this->stream,
            (int)$this->getWriteTimeoutSec(),
            $this->extractUsec($this->getWriteTimeoutSec())
        );

        return $this;
    }

    public function receive(): string
    {
        $delay = $this->getDelayRead();
        if ($delay > 0) {
            // this is useful slow serial devices that need delay between writing request to the serial device
            // and receiving response from device.
            usleep($delay);
        }
        $result = $this->receiveFrom([$this->stream], $this->getReadTimeoutSec(), $this->getLogger());
        return reset($result);
    }

    public function send(ModbusPacket|string $packet): BinaryStreamConnection
    {
        if (!\is_resource($this->stream) || @\feof($this->stream)) {
            throw new IOException('Can not write - stream closed by the peer');
        }
        $packetBytes = $packet;
        if ($packet instanceof ModbusPacket) {
            $packetBytes = $packet->__tostring();
        }

        fwrite($this->stream, $packetBytes, strlen($packetBytes));

        $this->logger?->debug('Data sent', unpack('H*', $packetBytes));

        return $this;
    }

    public function sendAndReceive(ModbusPacket|string $packet): string
    {
        return $this->send($packet)->receive();
    }

    public function close(): void
    {
        if (is_resource($this->stream)) {
            fclose($this->stream);
            $this->stream = null;
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
    private function extractUsec(float $seconds): int
    {
        return (int)(($seconds - (int)$seconds) * 1e6);
    }

    /**
     * @return resource|null
     */
    public function getStream()
    {
        return $this->stream;
    }
}
