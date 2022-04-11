<?php
declare(strict_types=1);

namespace ModbusTcpClient\Network;

use ModbusTcpClient\Exception\ModbusException;

/**
 * Exception class thrown when a binary stream network connection operation failure happens.
 */
class IOException extends ModbusException
{

}
