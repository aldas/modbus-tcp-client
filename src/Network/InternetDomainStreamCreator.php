<?php

namespace ModbusTcpClient\Network;

use InvalidArgumentException;

/**
 * Internet Domain: TCP, UDP
 */
class InternetDomainStreamCreator implements StreamCreator
{
    /**
     * @param BinaryStreamConnection $conn
     * @return resource
     */
    public function createStream(BinaryStreamConnection $conn)
    {
        $uri = $conn->getUri();
        if ($uri === null) {
            $protocol = strtolower($conn->getProtocol());
            if (!($protocol === StreamCreator::TYPE_TCP || $protocol === StreamCreator::TYPE_UDP)) {
                throw new InvalidArgumentException("Unknown protocol, should be 'TCP' or 'UDP'");
            }
            $uri = "{$protocol}://{$conn->getHost()}:{$conn->getPort()}";
        }

        $opts = [];
        if (strlen($conn->getClient()) > 0) {
            // Bind the client stream to a specific local network interface and port
            $opts = [
                'socket' => [
                    'bindto' => "{$conn->getClient()}:{$conn->getClientPort()}",
                ],
            ];
        }
        $context = stream_context_create($opts);

        $stream = @stream_socket_client(
            $uri,
            $errno,
            $errstr,
            $conn->getConnectTimeoutSec(),
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (false === $stream) {
            $message = "Unable to create client socket to {$uri}: {$errstr}";
            throw new IOException($message, $errno);
        }

        return $stream;
    }
}
