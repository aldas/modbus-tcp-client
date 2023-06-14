<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;


use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusApplicationHeader;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ModbusRequest;
use ModbusTcpClient\Packet\ProtocolDataUnitRequest;
use ModbusTcpClient\Utils\Endian;
use ModbusTcpClient\Utils\Registers;
use ModbusTcpClient\Utils\Types;

/**
 * Request for Read / Write Multiple Registers (FC=23)
 *
 * Example packet: \x01\x38\x00\x00\x00\x0f\x11\x17\x04\x10\x00\x01\x01\x12\x00\x02\x04\x00\xc8\x00\x82
 * \x01\x38 - transaction id
 * \x00\x00 - protocol id
 * \x00\x0f - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x11 - unit id
 * \x17 - function code
 * \x04\x10 - read registers start address
 * \x00\x01 - read registers quantity
 * \x01\x12 - write register start address
 * \x00\x02 - write quantity
 * \x04 - write bytes count
 * \x00\xc8\x00\x82 - write registers data (2 registers)
 *
 */
class ReadWriteMultipleRegistersRequest extends ProtocolDataUnitRequest implements ModbusRequest
{
    /** @var int $readQuantity quantity of registers to return in response */
    private int $readQuantity;
    /** @var int $writeStartAddress start of address where data is written from request */
    private int $writeStartAddress;

    /**
     * @var string[] registers (array of bytes)
     */
    private array $writeRegisters;
    private int $writeRegisterCount;
    private int $writeRegistersBytesSize;

    /**
     * @param int $readStartAddress
     * @param int $readQuantity
     * @param int $writeStartAddress
     * @param string[] $writeRegisters
     * @param int $unitId
     * @param int|null $transactionId
     */
    public function __construct(
        int   $readStartAddress,
        int   $readQuantity,
        int   $writeStartAddress,
        array $writeRegisters,
        int   $unitId = 0,
        int   $transactionId = null
    )
    {
        $this->readQuantity = $readQuantity;
        $this->writeStartAddress = $writeStartAddress;
        $this->writeRegisters = $writeRegisters;
        $this->writeRegistersBytesSize = Registers::getRegisterArrayByteSize($this->writeRegisters);
        $this->writeRegisterCount = (int)($this->writeRegistersBytesSize / 2);

        parent::__construct($readStartAddress, $unitId, $transactionId);

        $this->validate();
    }

    public function validate(): void
    {
        parent::validate();

        if ($this->readQuantity < 1 || $this->readQuantity > 125) {
            // there is no way more than 125 words (125*2 bytes) can be returned in response
            throw new InvalidArgumentException("read registers quantity out of range (1-125): {$this->readQuantity}");
        }

        if ($this->writeStartAddress < 0 || $this->writeStartAddress > Types::MAX_VALUE_UINT16) {
            throw new InvalidArgumentException("write registers start address out of range (0-65535): {$this->writeStartAddress}");
        }

        if ($this->writeRegisterCount === 0 || $this->writeRegisterCount > 121) {
            // there is no way more than 121 words (121*2 bytes) can be written in request
            throw new InvalidArgumentException("write registers count out of range (1-121): {$this->writeRegisterCount}");
        }
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::READ_WRITE_MULTIPLE_REGISTERS;
    }

    public function __toString(): string
    {
        return parent::__toString()
            . Types::toRegister($this->readQuantity)
            . Types::toRegister($this->writeStartAddress)
            . Types::toRegister($this->writeRegisterCount)
            . Types::toByte($this->writeRegistersBytesSize)
            . Registers::getRegisterArrayAsByteString($this->writeRegisters);
    }

    /**
     * @return string[]
     */
    public function getRegisters(): array
    {
        return $this->writeRegisters;
    }

    /**
     * @return int
     */
    public function getReadQuantity(): int
    {
        return $this->readQuantity;
    }

    /**
     * @return int
     */
    public function getWriteStartAddress(): int
    {
        return $this->writeStartAddress;
    }

    /**
     * @return int
     */
    public function getWriteRegisterCount(): int
    {
        return $this->writeRegisterCount;
    }

    protected function getLengthInternal(): int
    {
        // readQuantity size (2)) +
        // writeStartAddress size (2)) +
        // write registers count size (2) +
        // write register byte size (1) +
        // number of bytes registers need for data
        // = 7 bytes
        return parent::getLengthInternal() + (7 + $this->writeRegistersBytesSize);
    }

    /**
     * Parses binary string to ReadWriteMultipleRegistersRequest or return ErrorResponse on failure
     *
     * @param string $binaryString
     * @return ReadWriteMultipleRegistersRequest|ErrorResponse
     */
    public static function parse(string $binaryString): ErrorResponse|ReadWriteMultipleRegistersRequest
    {
        return self::parseStartAddressPacket(
            $binaryString,
            19,
            ModbusPacket::READ_WRITE_MULTIPLE_REGISTERS,
            function (int $transactionId, int $unitId, int $startAddress) use ($binaryString) {
                $readQuantity = Types::parseUInt16($binaryString[10] . $binaryString[11], Endian::BIG_ENDIAN);
                $writeStartAddress = Types::parseUInt16($binaryString[12] . $binaryString[13], Endian::BIG_ENDIAN);
                $writeQuantity = Types::parseUInt16($binaryString[14] . $binaryString[15], Endian::BIG_ENDIAN);
                $byteCount = Types::parseByte($binaryString[16]);
                $writeRegisters = str_split(substr($binaryString, 17, $byteCount), 2);
                if ($writeQuantity !== count($writeRegisters)) {
                    return new ErrorResponse(
                        new ModbusApplicationHeader(2, $unitId, $transactionId),
                        ModbusPacket::READ_WRITE_MULTIPLE_REGISTERS,
                        3 // Illegal data value
                    );
                }
                return new self(
                    $startAddress,
                    $readQuantity,
                    $writeStartAddress,
                    $writeRegisters,
                    $unitId,
                    $transactionId
                );
            }
        );
    }
}
