<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;


use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusRequest;
use ModbusTcpClient\Packet\ProtocolDataUnitRequest;
use ModbusTcpClient\Packet\Word;
use ModbusTcpClient\Utils\Endian;
use ModbusTcpClient\Utils\Types;

/**
 * Request for Mask Write Register (FC=22)
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
class MaskWriteRegisterRequest extends ProtocolDataUnitRequest implements ModbusRequest
{
    /**
     * @var int AND mask to be sent to modbus
     */
    private int $andMask;

    /**
     * @var int OR mask to be sent to modbus
     */
    private int $orMask;

    public function __construct(int $startAddress, int $andMask, int $orMask, int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($startAddress, $unitId, $transactionId);
        $this->andMask = $andMask;
        $this->orMask = $orMask;

        $this->validate();
    }

    public function validate(): void
    {
        parent::validate();
        // value is 2 bytes in packet so it in range of uint16 (0 - 65535) or int16 (-32768 - +32767)
        if (!(($this->andMask >= Types::MIN_VALUE_INT16) && ($this->andMask <= Types::MAX_VALUE_UINT16))) {
            throw new InvalidArgumentException("AND mask is out of range (u)int16: {$this->andMask}", 3);
        }
        if (!(($this->orMask >= Types::MIN_VALUE_INT16) && ($this->orMask <= Types::MAX_VALUE_UINT16))) {
            throw new InvalidArgumentException("OR mask is out of range (u)int16: {$this->orMask}", 3);
        }
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::MASK_WRITE_REGISTER;
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

    protected function getLengthInternal(): int
    {
        return parent::getLengthInternal() + 4; // AND mask size (2 bytes) + OR mask size (2 bytes)
    }

    /**
     * Parses binary string to MaskWriteRegisterRequest or return ErrorResponse on failure
     *
     * @param string $binaryString
     * @return MaskWriteRegisterRequest|ErrorResponse
     */
    public static function parse(string $binaryString): MaskWriteRegisterRequest|ErrorResponse
    {
        return self::parseStartAddressPacket(
            $binaryString,
            14,
            ModbusPacket::MASK_WRITE_REGISTER,
            function (int $transactionId, int $unitId, int $startAddress) use ($binaryString) {
                $andMask = Types::parseInt16($binaryString[10] . $binaryString[11], Endian::BIG_ENDIAN);
                $orMask = Types::parseInt16($binaryString[12] . $binaryString[13], Endian::BIG_ENDIAN);
                return new self($startAddress, $andMask, $orMask, $unitId, $transactionId);
            }
        );
    }
}
