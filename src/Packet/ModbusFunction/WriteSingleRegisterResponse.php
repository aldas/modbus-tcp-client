<?php
declare(strict_types=1);

namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\StartAddressResponse;
use ModbusTcpClient\Packet\Word;

/**
 * Response for Write Single Register (FC=06)
 *
 * Example packet: \x81\x80\x00\x00\x00\x06\x03\x06\x00\x02\xFF\x00
 * \x81\x80 - transaction id
 * \x00\x00 - protocol id
 * \x00\x06 - number of bytes in the message (PDU = ProtocolDataUnit) to follow
 * \x03 - unit id
 * \x06 - function code
 * \x00\x02 - start address
 * \xFF\x00 - register data
 *
 */
class WriteSingleRegisterResponse extends StartAddressResponse
{
    /**
     * @var Word
     */
    private Word $word;

    public function __construct(string $rawData, int $unitId = 0, int $transactionId = null)
    {
        parent::__construct($rawData, $unitId, $transactionId);
        $this->word = new Word(substr($rawData, 2, 2));
    }

    public function getFunctionCode(): int
    {
        return ModbusPacket::WRITE_SINGLE_REGISTER;
    }

    public function getWord(): Word
    {
        return $this->word;
    }

    protected function getLengthInternal(): int
    {
        return parent::getLengthInternal() + 2; //register is 2 bytes
    }

    public function __toString(): string
    {
        return parent::__toString()
            . $this->word->getData();
    }
}
