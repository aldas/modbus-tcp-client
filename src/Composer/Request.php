<?php
declare(strict_types=1);

namespace ModbusTcpClient\Composer;


use ModbusTcpClient\Packet\ModbusRequest;

interface Request
{
    public function parse(string $binaryData): mixed;

    public function getRequest(): ModbusRequest;

    public function getUri(): string;
}
