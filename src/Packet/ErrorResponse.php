<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet;


use ModbusTcpClient\Utils\Types;

/**
 * Modbus ErrorResponse packet
 *
 * Example packet: \xda\x87\x00\x00\x00\x03\x01\x81\x03
 * \xda\x87 - transaction id
 * \x00\x00 - protocol id
 * \x00\x03 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x01 - unit id
 * \x81 - function code + 128 (exception bitmask)
 * \x03 - error code
 *
 */
class ErrorResponse implements ModbusResponse
{
    /**
     * @var int Modbus exceptions are transferred in function code byte and have their high bit set (128)
     */
    const EXCEPTION_BITMASK = 128;

    /**
     * @var ModbusApplicationHeader
     */
    private ModbusApplicationHeader $header;

    /**
     * @var int
     */
    private int $functionCode;

    /**
     * @var int
     */
    private int $errorCode;

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
                $message = "Unknown error code ($this->errorCode)";
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

    public function withStartAddress(int $startAddress): static
    {
        return clone $this; // just to have same interface as 'success' responses
    }

    /**
     * is checks if given binary string is complete MODBUS TCP error packet
     * NB: do not use for RTU packets
     *
     * @param string|null $binaryData binary string to be checked
     * @return bool true if data is actual error packet
     */
    public static function is(string|null $binaryData): bool
    {
        // a) data is too short. can not determine packet.
        // b) data is too long. can not be an error packet
        // Actual packet is at least 9 bytes. 7 bytes for Modbus TCP header and at least 2 bytes for PDU
        if (strlen($binaryData) !== 9) {
            return false;
        }

        return (ord($binaryData[7]) & self::EXCEPTION_BITMASK) > 0;
    }
}
