<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\StartAddressResponse;
use ModbusTcpClient\Packet\Word;
use ModbusTcpClient\Utils\Endian;
use ModbusTcpClient\Utils\Types;

/**
 * Response for Mask Write Register (FC=22)
 *
 * Example packet: \x81\x80\x00\x00\x00\x08\x10\x16\x00\x04\x00\xF2\x00\x25
 * \x81\x80 - transaction id
 * \x00\x00 - protocol id
 * \x00\x08 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x10 - unit id
 * \x16 - function code
 * \x00\x04 - start address
 * \x00\xF2 - AND mask
 * \x00\x25 - OR mask
 *
 */
class MaskWriteRegisterResponse extends StartAddressResponse
{
    /**
     * @var int AND mask to be sent to modbus
     */
    private int $andMask;

    /**
     * @var int OR mask to be sent to modbus
     */
    private int $orMask;

    public function __construct(string $rawData, int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($rawData, $unitId, $transactionId);
        $this->andMask = Types::parseUInt16(substr($rawData, 2, 2), Endian::BIG_ENDIAN);
        $this->orMask = Types::parseUInt16(substr($rawData, 4, 2), Endian::BIG_ENDIAN);
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::MASK_WRITE_REGISTER;
    }

    protected function getLengthInternal(): int
    {
        return parent::getLengthInternal() + 4; //register is 4 bytes
    }

    public function __toString(): string
    {
        return parent::__toString()
            . Types::toRegister($this->getANDMask())
            . Types::toRegister($this->getORMask());
    }

    /**
     * @return int
     */
    public function getANDMask(): int
    {
        return $this->andMask;
    }

    /**
     * @return int
     */
    public function getORMask(): int
    {
        return $this->orMask;
    }

    /**
     * @return Word
     */
    public function getANDMaskAsWord(): Word
    {
        return new Word(Types::toInt16($this->andMask, Endian::BIG_ENDIAN));
    }

    /**
     * @return Word
     */
    public function getORMaskAsWord(): Word
    {
        return new Word(Types::toInt16($this->orMask, Endian::BIG_ENDIAN));
    }
}
