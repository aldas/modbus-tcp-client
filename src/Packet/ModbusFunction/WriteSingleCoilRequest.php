<?php
namespace ModbusTcpClient\Packet\ModbusFunction;

use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Packet\ProtocolDataUnitRequest;
use ModbusTcpClient\Utils\Types;


/**
 * Request for Write Single Coil (FC=05)
 */
class WriteSingleCoilRequest extends ProtocolDataUnitRequest
{
    const ON = 0xFF;
    const OFF = 0x0;

    /**
     * @var bool value to be sent to modbus
     */
    private $coil;

    public function __construct($startAddress, $coil, $unitId = 0, $transactionId = null)
    {
        parent::__construct($startAddress, $unitId, $transactionId);
        $this->coil = $coil;

        $this->validate();
    }

    public function validate()
    {
        parent::validate();
        if (!is_bool($this->coil)) {
            throw new \InvalidArgumentException('coil must be boolean value');
        }
    }

    public function getFunctionCode()
    {
        return IModbusPacket::WRITE_SINGLE_COIL;
    }

    public function __toString()
    {
        return parent::__toString()
            . Types::toByte($this->isCoil() ? self::ON : self::OFF)
            . chr(0x0);
    }

    /**
     * @return bool
     */
    public function isCoil()
    {
        return $this->coil;
    }

    protected function getLengthInternal()
    {
        return parent::getLengthInternal() + 2; // coil size (1 byte + 1 byte)
    }
}