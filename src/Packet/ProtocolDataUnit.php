<?php
declare(strict_types=1);

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
use ModbusTcpClient\Utils\Endian;
use ModbusTcpClient\Utils\Types;

abstract class ProtocolDataUnit implements ModbusPacket
{
    /**
     * @var ModbusApplicationHeader
     */
    private ModbusApplicationHeader $header;

    public function __construct(int $unitId = 0, int $transactionId = null)
    {
        $this->header = new ModbusApplicationHeader($this->getLengthInternal(), $unitId, $transactionId);
    }

    // lengthInternal is number of bytes in packet after unit id field in header
    abstract protected function getLengthInternal(): int;

    /**
     * @return ModbusApplicationHeader
     */
    public function getHeader(): ModbusApplicationHeader
    {
        return $this->header;
    }

    public function toHex(): string
    {
        return unpack('H*', $this->__toString())[1];
    }

    /**
     * @param string|null $binaryString
     * @param int $minLength
     * @param int $functionCode
     * @param callable $createFunctor
     * @return mixed|ErrorResponse
     */
    protected static function parsePacket(string|null $binaryString, int $minLength, int $functionCode, callable $createFunctor): mixed
    {
        if ($binaryString === null || strlen($binaryString) < $minLength) {
            return new ErrorResponse(new ModbusApplicationHeader(2, 0, 0),
                $functionCode,
                4 // Server failure
            );
        }

        $transactionId = Types::parseUInt16($binaryString[0] . $binaryString[1], Endian::BIG_ENDIAN);
        $unitId = Types::parseByte($binaryString[6]);
        if ($functionCode !== ord($binaryString[7])) {
            return new ErrorResponse(
                new ModbusApplicationHeader(2, $unitId, $transactionId),
                $functionCode,
                1 // Illegal function
            );
        }
        $pduLength = Types::parseUInt16($binaryString[4] . $binaryString[5], Endian::BIG_ENDIAN);
        if (($pduLength + 6) !== strlen($binaryString)) {
            return new ErrorResponse(
                new ModbusApplicationHeader(2, $unitId, $transactionId),
                $functionCode,
                3 // Illegal data value
            );
        }

        try {
            return $createFunctor($transactionId, $unitId);
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
