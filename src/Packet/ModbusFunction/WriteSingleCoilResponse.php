<?php
namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Packet\ProtocolDataUnitResponse;

/**
 * Response for Write Single Coil (FC=05)
 */
class WriteSingleCoilResponse extends ProtocolDataUnitResponse
{

    public function getFunctionCode()
    {
        return IModbusPacket::WRITE_SINGLE_COIL;
    }

    public static function parse($binaryString)
    {
        // TODO: Implement parse() method.
    }

    public function isCoil()
    {
        $response = $this->getData();
        return $response[0] === 0xFF && $response[1] === 0x0;
    }
}