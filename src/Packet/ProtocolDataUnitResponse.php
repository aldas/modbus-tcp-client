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

use ModbusTcpClient\Utils\Types;

abstract class ProtocolDataUnitResponse extends ProtocolDataUnit
{
    /**
     * @var string
     */
    private $rawData;

    /**
     * @var int
     */
    private $length;

    public function __construct($rawData, $unitId = 0, $transactionId = null)
    {
        $this->rawData = $rawData;
        $this->length = strlen($this->rawData);

        parent::__construct($unitId, $transactionId);
    }

    public function __toString()
    {
        return b''
            . $this->getHeader()
            . Types::toByte($this->getFunctionCode())
            . Types::toByte($this->getLength())
            . $this->getRawData();
    }

    public function getLength()
    {
        return $this->length;
    }

    protected function getLengthInternal()
    {
        return $this->getLength() + 2; // 2 is for function code + data length
    }

    /**
     * @return string
     */
    public function getRawData()
    {
        return $this->rawData;
    }


    public function getData()
    {
        return array_values(unpack('C*', $this->rawData));
    }
}