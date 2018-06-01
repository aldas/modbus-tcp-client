<?php

namespace Tests\Utils;

use ModbusTcpClient\Utils\Endian;
use ModbusTcpClient\Utils\Registers;
use ModbusTcpClient\Utils\Types;
use PHPUnit\Framework\TestCase;

class RegistersTest extends TestCase
{
    public function testRegisterArrayByteSize()
    {
        $this->assertEquals(2, Registers::getRegisterArrayByteSize([Types::toByte(200)]));
        $this->assertEquals(4, Registers::getRegisterArrayByteSize([null, Types::toByte(200)]));

        $this->assertEquals(4, Registers::getRegisterArrayByteSize([Types::toByte(200), Types::toInt16(1)]));
        $this->assertEquals(6, Registers::getRegisterArrayByteSize([Types::toInt16(1), Types::toByte(200), Types::toInt16(1)]));
    }

    public function testRegisterArrayAsByteString()
    {
        $this->assertEquals("\x0\xC8", Registers::getRegisterArrayAsByteString([Types::toByte(200)]));
        $this->assertEquals("\x0\x0\x0\xC8", Registers::getRegisterArrayAsByteString([null, Types::toByte(200)]));

        $this->assertEquals("\x0\xC8\x01\x01", Registers::getRegisterArrayAsByteString([Types::toByte(200), Types::toInt16(257)]));
        $this->assertEquals("\x0\x01\x0\xC8\x0\x0", Registers::getRegisterArrayAsByteString([Types::toInt16(1), Types::toByte(200), null]));

        $this->assertEquals("\x00\x01\x02\x03", Registers::getRegisterArrayAsByteString(["\x01\x02\x03"])); //FIX should we allow odd bytes to be sent?

        $this->assertEquals("\x07\xd0\x00\x00", Registers::getRegisterArrayAsByteString([Types::toInt32(2000, Endian::BIG_ENDIAN_LOW_WORD_FIRST)]));
    }

    public function testLowWordIsSentFirst()
    {
        $this->assertEquals("\x56\x52\xAE\x41", Registers::getRegisterArrayAsByteString([Types::toUint32(2923517522, Endian::BIG_ENDIAN_LOW_WORD_FIRST)]));
    }

}