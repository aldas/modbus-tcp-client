<?php

namespace ModbusTcpClient\Network;


interface StreamCreator
{
    const TYPE_TCP = 'tcp';
    const TYPE_UDP = 'udp';
    const TYPE_SERIAL = 'serial';

    public function createStream(BinaryStreamConnection $conn);
}
