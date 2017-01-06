<?php


namespace ModbusTcpClient\Packet;


use ModbusTcpClient\Utils\Types;

class ExceptionResponse implements IModbusPacket
{
    /**
     * @var int Modbus exceptions are transfered in function code byte and have their high bit set (128)
     */
    const EXCEPTION_BITMASK = 128;

    /**
     * @var ModbusApplicationHeader
     */
    private $header;

    /**
     * @var int
     */
    private $functionCode;

    /**
     * @var int
     */
    private $errorCode;

    public function __construct(ModbusApplicationHeader $header, $functionCode, $errorCode)
    {
        $this->header = $header;
        $this->functionCode = $functionCode;
        $this->errorCode = $errorCode;
    }

    public function getHeader()
    {
        return $this->header;
    }

    public function getFunctionCode()
    {
        return $this->functionCode;
    }

    public function getErrorCode()
    {
        return $this->errorCode;
    }

    public function getLength()
    {
        return $this->header->getLength() + 2; // 2 bytes for function code and error code
    }

    public function __toString()
    {
        return b''
            . $this->getHeader()
            . Types::toByte($this->getFunctionCode() & self::EXCEPTION_BITMASK)
            . Types::toByte($this->getErrorCode());
    }

    public function toHex() {
        return unpack('H*', $this->__toString())[1];
    }
}