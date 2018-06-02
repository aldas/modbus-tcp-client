<?php


namespace ModbusTcpClient\Packet;


use ModbusTcpClient\Utils\Types;

class ErrorResponse implements ModbusResponse
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

    public function __construct(ModbusApplicationHeader $header, int $functionCode, int $errorCode)
    {
        $this->header = $header;
        $this->functionCode = $functionCode;
        $this->errorCode = $errorCode;
    }

    public function getHeader(): ModbusApplicationHeader
    {
        return $this->header;
    }

    public function getFunctionCode(): int
    {
        return $this->functionCode;
    }

    public function getErrorCode(): int
    {
        return $this->errorCode;
    }

    public function getErrorMessage(): string
    {
        switch ($this->errorCode) {
            case 1:
                $message = 'Illegal function';
                break;
            case 2:
                $message = 'Illegal data address';
                break;
            case 3:
                $message = 'Illegal data value';
                break;
            case 4:
                $message = 'Server failure';
                break;
            case 5:
                $message = 'Acknowledge';
                break;
            case 6:
                $message = 'Server busy';
                break;
            case 10:
                $message = 'Gateway path unavailable';
                break;
            case 11:
                $message = 'Gateway targeted device failed to respond';
                break;
            default:
                $message = "Uknown error code ($this->errorCode)";
                break;
        }
        return $message;
    }

    public function getLength(): int
    {
        return 2; // 2 bytes for function code and error code
    }

    public function __toString(): string
    {
        return b''
            . $this->getHeader()
            . Types::toByte($this->getFunctionCode() + self::EXCEPTION_BITMASK)
            . Types::toByte($this->getErrorCode());
    }

    public function toHex(): string
    {
        return unpack('H*', $this->__toString())[1];
    }

    public function withStartAddress(int $startAddress)
    {
        return clone $this; // just to have same interface as 'success' responses
    }
}