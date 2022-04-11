<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusRequest;
use ModbusTcpClient\Packet\ProtocolDataUnitRequest;
use ModbusTcpClient\Utils\Types;


/**
 * Request for Write Single Coil (FC=05)
 *
 * Example packet: \x00\x01\x00\x00\x00\x06\x11\x05\x00\x6B\xFF\x00
 * \x00\x01 - transaction id
 * \x00\x00 - protocol id
 * \x00\x06 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x11 - unit id
 * \x05 - function code
 * \x00\x6B - start address
 * \xFF\x00 - coil data (true)
 *
 */
class WriteSingleCoilRequest extends ProtocolDataUnitRequest implements ModbusRequest
{
    const ON = 0xFF;
    const OFF = 0x0;

    /**
     * @var bool value to be sent to modbus
     */
    private bool $coil;

    public function __construct(int $startAddress, bool $coil, int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($startAddress, $unitId, $transactionId);
        $this->coil = $coil;

        $this->validate();
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::WRITE_SINGLE_COIL;
    }

    public function __toString(): string
    {
        return parent::__toString()
            . Types::toByte($this->isCoil() ? self::ON : self::OFF)
            . chr(0x0);
    }

    /**
     * @return bool
     */
    public function isCoil(): bool
    {
        return $this->coil;
    }

    protected function getLengthInternal(): int
    {
        return parent::getLengthInternal() + 2; // coil size (1 byte + 1 byte)
    }

    /**
     * Parses binary string to WriteSingleCoilRequest or return ErrorResponse on failure
     *
     * @param string $binaryString
     * @return WriteSingleCoilRequest|ErrorResponse
     */
    public static function parse(string $binaryString): ErrorResponse|WriteSingleCoilRequest
    {
        return self::parseStartAddressPacket(
            $binaryString,
            12,
            ModbusPacket::WRITE_SINGLE_COIL,
            function (int $transactionId, int $unitId, int $startAddress) use ($binaryString) {
                $coil = Types::parseByte($binaryString[10]) === self::ON;
                return new self($startAddress, $coil, $unitId, $transactionId);
            }
        );
    }
}
