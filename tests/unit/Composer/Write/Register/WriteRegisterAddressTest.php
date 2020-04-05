<?php

namespace Tests\unit\Composer\Write\Register;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Composer\Write\Register\WriteRegisterAddress;
use PHPUnit\Framework\TestCase;

class WriteRegisterAddressTest extends TestCase
{
    public function testGetValue()
    {
        $address = new WriteRegisterAddress(0, Address::TYPE_INT64, 1);

        $this->assertEquals(1, $address->getValue());
    }


    /**
     * @dataProvider toBinaryProvider
     */
    public function testToBinary(string $expectedBinaryString, string $type, $value, $expectedException = null, $skipOn32Bit = false)
    {
        if ($skipOn32Bit && PHP_INT_SIZE === 4) {
            $this->markTestSkipped('32-bit version of PHP');
        }

        if ($expectedException !== null) {
            $this->expectException($expectedException);
        }
        $address = new WriteRegisterAddress(0, $type, $value);

        $this->assertEquals($expectedBinaryString, $address->toBinary());
    }

    public function toBinaryProvider()
    {
        return [
            'int16 to binary' => ["\xFF\xFF", Address::TYPE_INT16, -1],
            'uint16 to binary' => ["\xFF\xFF", Address::TYPE_UINT16, 65535],
            'int32 to binary' => ["\x00\x00\x80\x00", Address::TYPE_INT32, -2147483648],
            'uint32 to binary' => ["\x00\x01\x00\x00", Address::TYPE_UINT32, 1],
            'int64 to binary' => ["\x00\x00\x00\x00\x00\x00\x80\x00", Address::TYPE_INT64, -9223372036854775808, null, true],
            'uint64 to binary' => ["\x00\x01\x00\x00\x00\x00\x00\x00", Address::TYPE_UINT64, 1, null, true],
            'float to binary' => ["\xcc\xcd\x3f\xec", Address::TYPE_FLOAT, 1.85],
            'string to binary' => ["\x00\x6E\x65\x72\xF8\x53", Address::TYPE_STRING, 'Søren', \ModbusTcpClient\Exception\InvalidArgumentException::class],
        ];
    }

}
