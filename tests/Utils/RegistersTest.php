<?php

namespace Tests\Utils;

use ModbusTcpClient\Utils\Registers;
use ModbusTcpClient\Utils\Types;
use PHPUnit\Framework\TestCase;

class RegistersTest extends TestCase
{
    public function testRegisterArrayByteSize()
    {
        $this->assertEquals(2, Registers::getRegisterArrayByteSize([Types::toByte(200)]));
        $this->assertEquals(4, Registers::getRegisterArrayByteSize([null, Types::toByte(200)]));

        $this->assertEquals(4, Registers::getRegisterArrayByteSize([Types::toByte(200), Types::toUInt16BE(1)]));
        $this->assertEquals(6, Registers::getRegisterArrayByteSize([Types::toUInt16BE(1), Types::toByte(200), Types::toUInt16BE(1)]));
    }

    public function testRegisterArrayAsByteString()
    {
        $this->assertEquals("\x0\xC8", Registers::getRegisterArrayAsByteString([Types::toByte(200)]));
        $this->assertEquals("\x0\x0\x0\xC8", Registers::getRegisterArrayAsByteString([null, Types::toByte(200)]));

        $this->assertEquals("\x0\xC8\x01\x01", Registers::getRegisterArrayAsByteString([Types::toByte(200), Types::toUInt16BE(257)]));
        $this->assertEquals("\x0\x01\x0\xC8\x0\x0", Registers::getRegisterArrayAsByteString([Types::toUInt16BE(1), Types::toByte(200), null]));
    }

}