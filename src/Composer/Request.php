<?php

namespace ModbusTcpClient\Composer;


interface Request
{
    public function parse(string $binaryData);

    public function getRequest();

    public function getUri(): string;
}