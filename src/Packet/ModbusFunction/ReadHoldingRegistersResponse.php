<?php
namespace ModbusTcpClient\Packet\ModbusFunction;


use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Packet\ProtocolDataUnitResponse;
use ModbusTcpClient\Utils\Types;

/**
 * Response for Read Holding Registers (FC=03)
 */
class ReadHoldingRegistersResponse extends ProtocolDataUnitResponse
{

    public function getFunctionCode()
    {
        return IModbusPacket::READ_HOLDING_REGISTERS;
    }

    public function __toString()
    {
        return b''
            . $this->getHeader()
            . Types::toByte($this->getFunctionCode())
            . $this->getRawData();

    }

    public static function parse($binaryString)
    {
        // TODO: Implement parse() method.
    }
}
