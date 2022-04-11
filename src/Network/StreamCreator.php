<?php
declare(strict_types=1);

namespace ModbusTcpClient\Network;


interface StreamCreator
{
    const TYPE_TCP = 'tcp';
    const TYPE_UDP = 'udp';
    const TYPE_SERIAL = 'serial';

    /**
     * @param BinaryStreamConnection $conn
     * @return resource
     */
    public function createStream(BinaryStreamConnection $conn);
}
