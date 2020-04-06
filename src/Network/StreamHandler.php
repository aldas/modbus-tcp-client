<?php

namespace ModbusTcpClient\Network;


trait StreamHandler
{
    /**
     * @param array $readStreams
     * @param float $timeout
     * @param \Psr\Log\LoggerInterface $logger
     * @return array
     */
    protected function receiveFrom(array $readStreams, float $timeout = null, $logger = null): array
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

            if (false === stream_select(
                    $read,
                    $write,
                    $except,
                    (int)$timeout,
                    $timeoutUsec
                )
            ) {
                throw new IOException('stream_select interrupted by an incoming signal');
            }

            if ($logger) {
                $logger->debug('Polling data');
            }

            $dataReceived = false;
            foreach ($read as $stream) {
                $streamId = (int)$stream;

                $index = $streamMap[$streamId] ?? null;
                if ($index !== null) {
                    /** as Modbus packets are small enough to fit into single read we are just waiting to first response
                     * from fread and then mark stream as processed.
                     *
                     * BE WARNED: So if would try to use same method to download HTML pages
                     * or anything larger you would find this approach not working as expected.
                     */
                    $data = fread($stream, 256); // read max 256 bytes
                    if (!empty($data)) {
                        if ($logger) {
                            $logger->debug("Stream {$streamId} @ index: {$index} received data: ", unpack('H*', $data));
                        }
                        $result[$index] = $data;
                        $responsesToWait--;

                        // if we received data to at least one stream we were waiting then it is good enough stream_select cycle
                        $dataReceived = true;
                    }
                }
            }

            if (!$dataReceived) {
                $timeSpentWaiting = microtime(true) - $lastAccess;
                if ($timeSpentWaiting >= $timeout) {
                    throw new IOException('Read total timeout expired');
                }
            }
            $lastAccess = microtime(true);
        }
        return $result;
    }
}
