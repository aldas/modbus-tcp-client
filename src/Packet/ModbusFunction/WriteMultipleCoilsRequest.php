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
use ModbusTcpClient\Utils\Types;

/**
 * Request for Write Multiple Coils (FC=15)
 *
 * Example packet: \x01\x38\x00\x00\x00\x08\x11\x0F\x04\x10\x00\x03\x01\x05
 * \x01\x38 - transaction id
 * \x00\x00 - protocol id
 * \x00\x08 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x11 - unit id
 * \x0F - function code
 * \x04\x10 - start address
 * \x00\x03 - count of coils to write
 * \x01 - coils byte count
 * \x05 - coils data
 *
 */
class WriteMultipleCoilsRequest extends ProtocolDataUnitRequest implements ModbusRequest
{
    /**
     * @var bool[] coils (array of booleans)
     */
    private array $coils;
    private int $coilCount;
    private int $coilBytesSize;

    /**
     * @param int $startAddress
     * @param bool[] $coils
     * @param int $unitId
     * @param int|null $transactionId
     */
    public function __construct(int $startAddress, array $coils, int $unitId = 0, int $transactionId = null)
    {
        $this->coils = $coils;
        $this->coilCount = count($this->coils);
        $this->coilBytesSize = (int)(($this->coilCount + 7) / 8);

        parent::__construct($startAddress, $unitId, $transactionId);

        $this->validate();
    }

    public function validate(): void
    {
        parent::validate();

        if ($this->coilCount === 0 || $this->coilCount > 2048) {
            throw new InvalidArgumentException("coils count out of range (1-2048): {$this->coilCount}", 3);
        }
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::WRITE_MULTIPLE_COILS;
    }

    public function __toString(): string
    {
        return parent::__toString()
            . Types::toRegister($this->coilCount)
            . Types::toByte($this->coilBytesSize)
            . Types::byteArrayToByte(Types::booleanArrayToByteArray($this->coils));
    }

    /**
     * @return bool[]
     */
    public function getCoils(): array
    {
        return $this->coils;
    }

    protected function getLengthInternal(): int
    {
        return parent::getLengthInternal() + (3 + $this->coilBytesSize); // coilCount + coilBytesSize + number of bytes coils need for data
    }

    /**
     * Parses binary string to WriteMultipleCoilsRequest or return ErrorResponse on failure
     *
     * @param string $binaryString
     * @return WriteMultipleCoilsRequest|ErrorResponse
     */
    public static function parse(string $binaryString): ErrorResponse|WriteMultipleCoilsRequest
    {
        return self::parseStartAddressPacket(
            $binaryString,
            14,
            ModbusPacket::WRITE_MULTIPLE_COILS,
            function (int $transactionId, int $unitId, int $startAddress) use ($binaryString) {
                $quantity = Types::parseUInt16($binaryString[10] . $binaryString[11], Endian::BIG_ENDIAN);
                $byteCount = Types::parseByte($binaryString[12]);
                $coils = Types::binaryStringToBooleanArray(substr($binaryString, 13, $byteCount));
                if ($quantity > count($coils)) {
                    return new ErrorResponse(
                        new ModbusApplicationHeader(2, $unitId, $transactionId),
                        ModbusPacket::WRITE_MULTIPLE_COILS,
                        3 // Illegal data value
                    );
                }
                $coils = array_slice($coils, 0, $quantity);
                return new self($startAddress, $coils, $unitId, $transactionId);
            }
        );
    }
}
