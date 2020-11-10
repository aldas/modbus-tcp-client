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

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Utils\Types;

abstract class ProtocolDataUnitRequest extends ProtocolDataUnit
{
    private $startAddress;

    public function __construct(int $startAddress, int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($unitId, $transactionId);

        $this->startAddress = $startAddress;
    }

    public function __toString(): string
    {
        return b''
            . $this->getHeader()->__toString()
            . Types::toByte($this->getFunctionCode())
            . Types::toUint16($this->getStartAddress());
    }

    public function getStartAddress(): int
    {
        return $this->startAddress;
    }

    protected function getLengthInternal(): int
    {
        return 3; // size of function code (1 byte) + startAddress (2 bytes)
    }

    protected function validate()
    {
        if ((null === $this->startAddress) || !($this->startAddress >= 0 && $this->startAddress <= Types::MAX_VALUE_UINT16)) {
            throw new InvalidArgumentException("startAddress is not set or out of range: {$this->startAddress}", 2);
        }
    }

    /**
     * @param $binaryString
     * @param $minLength
     * @param $functionCode
     * @param $createFunctor
     * @return mixed|ErrorResponse
     */
    protected static function parseStartAddressPacket($binaryString, int $minLength, int $functionCode, $createFunctor)
    {
        if ($binaryString === null || strlen($binaryString) < $minLength) {
            return new ErrorResponse(new ModbusApplicationHeader(2, 0, 0),
                $functionCode,
                4 // Server failure
            );
        }

        $transactionId = Types::parseUInt16($binaryString[0] . $binaryString[1]);
        $unitId = Types::parseByte($binaryString[6]);
        if ($functionCode !== ord($binaryString[7])) {
            return new ErrorResponse(
                new ModbusApplicationHeader(2, $unitId, $transactionId),
                $functionCode,
                1 // Illegal function
            );
        }
        $pduLength = Types::parseUInt16($binaryString[4] . $binaryString[5]);
        if (($pduLength + 6) !== strlen($binaryString)) {
            return new ErrorResponse(
                new ModbusApplicationHeader(2, $unitId, $transactionId),
                $functionCode,
                3 // Illegal data value
            );
        }

        $startAddress = Types::parseUInt16($binaryString[8] . $binaryString[9]);
        try {
            return $createFunctor($transactionId, $unitId, $startAddress);
        } catch (\Exception $exception) {
            // constructor does validation and throws exception so not to mix returning errors and throwing exceptions
            // we catch exception here and return it as a error response.
            $errorCode = $exception instanceof InvalidArgumentException ? $exception->getCode() : 3; // Illegal data value
            return new ErrorResponse(
                new ModbusApplicationHeader(2, $unitId, $transactionId),
                $functionCode,
                $errorCode
            );
        }
    }

}
