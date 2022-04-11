<?php
declare(strict_types=1);

namespace ModbusTcpClient\Network;

use Psr\Log\LoggerInterface;

trait StreamHandler
{
    /**
     * receiveFrom reads data from multiple streams asynchronously and returns when at least 1 full packet is received
     * NB: return when 1 full packet is silly and you should not use this method for multiple streams.
     * for "backwards compatibility" this is not changed/fixed atm
     *
     * @param resource[] $readStreams
     * @param float|null $timeout
     * @param LoggerInterface|null $logger
     * @return string[]
     */
    protected function receiveFrom(array $readStreams, float $timeout = null, LoggerInterface $logger = null): array
    {
        if ($timeout === null) {
            $timeout = 0.3;
        }

        $responsesToWait = \count($readStreams);
        // map streams by their ID so we could reliably return data when we receive it in different order
        $streamMap = [];
        foreach ($readStreams as $indexOrKey => $stream) {
            $streamMap[(int)$stream] = $indexOrKey;
        }

        $result = [];
        $lastAccess = microtime(true);
        $timeoutUsec = (int)(($timeout - (int)$timeout) * 1e6);
        $write = [];
        $except = [];
        while ($responsesToWait > 0) {
            $read = $readStreams;

            /**
             * On success stream_select returns the number of
             * stream resources contained in the modified arrays, which may be zero if
             * the timeout expires before anything interesting happens. On error false
             * is returned and a warning raised (this can happen if the system call is
             * interrupted by an incoming signal).
             */
            $modifiedStreams = stream_select(
                $read,
                $write,
                $except,
                (int)$timeout,
                $timeoutUsec
            );
            if (false == $modifiedStreams) {
                throw new IOException('stream_select interrupted by an incoming signal');
            }

            $logger?->debug('Polling data');

            $dataReceived = false;
            foreach ($read as $stream) {
                $streamId = (int)$stream;

                $streamIndex = $streamMap[$streamId] ?? null;
                if ($streamIndex !== null) {
                    $data = fread($stream, 256); // read max 256 bytes
                    if ($data === false) {
                        throw new IOException('fread error during receiveFrom');
                    }
                    if (!empty($data)) {
                        $logger?->debug("Stream {$streamId} @ index: {$streamIndex} received data: ", unpack('H*', $data));
                        $packetData = ($result[$streamIndex] ?? b'') . $data;
                        $result[$streamIndex] = $packetData;

                        // MODBUS SPECIFIC PART: if we received complete packet to at least one stream we were waiting
                        // then it is good enough stream_select cycle
                        if ($this->getIsCompleteCallback()($packetData, $streamIndex)) {
                            // happy path, got exactly what we expect
                            // or response is an modbus error packet. nothing to wait anymore
                            $responsesToWait--;

                            $dataReceived = true;
                        }
                    }
                }
            }

            if (!$dataReceived) {
                $timeSpentWaiting = microtime(true) - $lastAccess;
                if ($timeSpentWaiting >= $timeout) {
                    throw new IOException('Read total timeout expired');
                }
            } else {
                $lastAccess = microtime(true);
            }

        }
        return $result;
    }
}
