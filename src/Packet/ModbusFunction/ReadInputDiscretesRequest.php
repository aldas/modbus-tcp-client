<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Endian;
use ModbusTcpClient\Utils\Types;

/**
 * Request for Read Input Discretes (FC=02)
 *
 * Example packet: \x81\x80\x00\x00\x00\x06\x10\x02\x00\x6B\x00\x03
 * \x81\x80 - transaction id
 * \x00\x00 - protocol id
 * \x00\x06 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x10 - unit id
 * \x02 - function code
 * \x00\x6B - start address
 * \x00\x03 - input discretes quantity to return
 *
 */
class ReadInputDiscretesRequest extends ReadCoilsRequest
{
    public function getFunctionCode(): int
    {
        return ModbusPacket::READ_INPUT_DISCRETES;
    }

    /**
     * Parses binary string to ReadInputDiscretesRequest or return ErrorResponse on failure
     *
     * @param string $binaryString
     * @return ReadInputDiscretesRequest|ErrorResponse
     */
    public static function parse(string $binaryString): ReadInputDiscretesRequest|ErrorResponse
    {
        return self::parseStartAddressPacket(
            $binaryString,
            12,
            ModbusPacket::READ_INPUT_DISCRETES,
            function (int $transactionId, int $unitId, int $startAddress) use ($binaryString) {
                $quantity = Types::parseUInt16($binaryString[10] . $binaryString[11], Endian::BIG_ENDIAN);
                return new self($startAddress, $quantity, $unitId, $transactionId);
            }
        );
    }
}
