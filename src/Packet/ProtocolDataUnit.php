<?php

namespace ModbusTcpClient\Packet;


/*
 * Here is an example of a Modbus RTU request for the content of analog output holding registers # 40108 to 40110.
 * 03 006B 0003
 *
 * 03: The Function Code (read Analog Output Holding Registers)
 * 006B: The Data Address of the first register requested. (40108-40001 = 107 =6B hex)
 * 0003: The total number of registers requested. (read 3 registers 40108 to 40110)
 */

abstract class ProtocolDataUnit implements IModbusPacket
{
    /**
     * @var ModbusApplicationHeader
     */
    private $header;

    public function __construct($unitId = 0, $transactionId = null)
    {
        $this->header = new ModbusApplicationHeader($this->getLength(), $unitId, $transactionId);
    }

    /**
     * @return ModbusApplicationHeader
     */
    public function getHeader()
    {
        return $this->header;
    }
}