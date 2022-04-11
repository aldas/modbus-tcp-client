<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\StartAddressResponse;
use ModbusTcpClient\Utils\Types;

/**
 * Response for Write Single Coil (FC=05)
 *
 * Data part of packet is always 4 bytes - 2 byte for address and 2 byte for coil status (FF00 = on,  0000 = off).
 * For example: coil at address 1 is turned on '\x00\x01\xFF\x00'
 * For example: coil at address 10 is turned off '\x00\x0A\x00\x00'
 *
 * Example packet: \x00\x01\x00\x00\x00\x06\x03\x05\x00\x02\xFF\x00
 * \x00\x01 - transaction id
 * \x00\x00 - protocol id
 * \x00\x06 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x03 - unit id
 * \x05 - function code
 * \x00\x02 - start address
 * \xFF\x00 - coil data (true)
 *
 */
class WriteSingleCoilResponse extends StartAddressResponse
{
    const ON = 0xFF;
    const OFF = 0x0;

    /**
     * @var bool
     */
    private bool $coil;

    public function __construct(string $rawData, int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($rawData, $unitId, $transactionId);
        $this->coil = ord($rawData[2]) === self::ON;
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::WRITE_SINGLE_COIL;
    }

    public function __toString(): string
    {
        return parent::__toString()
            . Types::toByte($this->isCoil() ? self::ON : self::OFF)
            . chr(self::OFF);
    }

    public function isCoil(): bool
    {
        return $this->coil;
    }

    protected function getLengthInternal(): int
    {
        return parent::getLengthInternal() + 2; //coil 1 byte + 1 "unused 0x0 byte"
    }
}
