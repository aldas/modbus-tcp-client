<?php

namespace ModbusTcpClient\Packet;


interface IModbusPacket
{
    public function getHeader();
    public function getFunctionCode();
    public function getLength();

    public function __toString();
    public static function parse($binaryString);

}