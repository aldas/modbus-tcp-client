<?php

declare(strict_types=1);

namespace ModbusTcpClient\Network;

interface StreamCreator
{
    public const TYPE_TCP = 'tcp';
    public const TYPE_UDP = 'udp';
    public const TYPE_SERIAL = 'serial';

    /**
     * @param BinaryStreamConnection $conn
     * @return resource
     */
    public function createStream(BinaryStreamConnection $conn);
}
