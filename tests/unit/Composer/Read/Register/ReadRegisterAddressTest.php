<?php

namespace Tests\unit\Composer\Read\Register;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Composer\Read\Register\ReadRegisterAddress;
use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class ReadRegisterAddressTest extends TestCase
{
    /**
     * @dataProvider sizeProvider
     */
    public function testGetSize($type, $expectedSize)
    {
        $address = new ReadRegisterAddress(1, $type);
        $this->assertEquals($expectedSize, $address->getSize());
    }

    public function sizeProvider()
    {
        return [
            'int16 size should be 1' => ['int16', 1],
            'uint16 size should be 1' => ['uint16', 1],
            'int32 size should be 2' => ['int32', 2],
            'float size should be 2' => ['float', 2],
            'uint32 size should be 2' => ['uint32', 2],
            'uint64 size should be 4' => ['uint64', 4],
            'double size should be 4' => ['double', 4],
        ];
    }

    public function testInvalidType()
    {
        $this->expectExceptionMessage("Invalid address type given! type: 'byte', address: 1");
        $this->expectException(InvalidArgumentException::class);

        new ReadRegisterAddress(1, Address::TYPE_BYTE);
    }

    public function testGetName()
    {
        $address = new ReadRegisterAddress(1, Address::TYPE_INT16, 'temp1');
        $this->assertEquals('temp1', $address->getName());
    }

    public function testGetEndian()
    {
        $address = new ReadRegisterAddress(1, Address::TYPE_INT16, 'temp1', null, null, Endian::BIG_ENDIAN);
        $this->assertEquals(Endian::BIG_ENDIAN, $address->getEndian());
    }

    public function testDefaultGetName()
    {
        $address = new ReadRegisterAddress(1, Address::TYPE_INT16);
        $this->assertEquals('int16_1', $address->getName());
    }

    public function testGetAddress()
    {
        $address = new ReadRegisterAddress(1, Address::TYPE_INT16);
        $this->assertEquals(1, $address->getAddress());
    }

    public function testExtractWithCallback()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x01\x80\x00\x00\x03\x00\x04", 3, 33152);

        $cbAddress = null;
        $cbResponse = null;
        $address = new ReadRegisterAddress(
            1,
            Address::TYPE_UINT16,
            null,
            function ($data, $address, $response) use (&$cbAddress, &$cbResponse) {
                $cbAddress = $address;
                $cbResponse = $response;
                return 'prefix_' . $data;
            }
        );

        $value = $address->extract($responsePacket);

        $this->assertEquals('prefix_32768', $value);
        $this->assertEquals($responsePacket, $cbResponse);
        $this->assertEquals($address, $cbAddress);
    }

    public function testErrorCallbackWhenParsingFails()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF", 3, 33152);

        $errCbAddress = null;
        $errCbResponse = null;
        $address = new ReadRegisterAddress(
            0,
            Address::TYPE_UINT64,
            null,
            function ($data, $address, $response) {
                return 'prefix_' . $data;
            },
            function (\Exception $exception, $address, $response) use (&$errCbAddress, &$errCbResponse) {
                $errCbAddress = $address;
                $errCbResponse = $response;
                return get_class($exception) . '_' . $exception->getMessage();
            }
        );

        $value = $address->extract($responsePacket);

        $error = 'ModbusTcpClient\Exception\OverflowException_64-bit PHP supports only up to 63-bit signed integers. Current input has 64th bit set and overflows. Hex: ffffffffffffffff';
        if (PHP_INT_SIZE === 4) {
            $error = 'ModbusTcpClient\Exception\ParseException_64-bit format codes are not available for 32-bit versions of PHP';
        }
        $this->assertEquals($error, $value);

        $this->assertEquals($responsePacket, $errCbResponse);
        $this->assertEquals($address, $errCbAddress);
    }

    public function testErrorCallbackWhenCbFails()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x00\x00\x00\x00\x00\x00\x00", 3, 33152);

        $errCbAddress = null;
        $errCbResponse = null;
        $address = new ReadRegisterAddress(
            0,
            Address::TYPE_INT16,
            null,
            function () {
                throw new \RuntimeException('catch me');
            },
            function (\Exception $exception, $address, $response) use (&$errCbAddress, &$errCbResponse) {
                $errCbAddress = $address;
                $errCbResponse = $response;
                return get_class($exception) . '_' . $exception->getMessage();
            }
        );

        $value = $address->extract($responsePacket);

        $this->assertEquals('RuntimeException_catch me', $value);
        $this->assertEquals($responsePacket, $errCbResponse);
        $this->assertEquals($address, $errCbAddress);
    }


    public function testExtractWithNoErrorCallback()
    {
        $this->expectExceptionMessage("test");
        $this->expectException(\RuntimeException::class);

        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x00\x00\x00\x00\x00\x00\x00", 3, 33152);

        $address = new ReadRegisterAddress(
            0,
            Address::TYPE_INT16,
            null,
            function () {
                throw new \RuntimeException('test');
            }
        );

        $address->extract($responsePacket);
    }

    public function testExtractInt16()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x01\x80\x00\x00\x03\x00\x04", 3, 33152);
        $address = new ReadRegisterAddress(1, Address::TYPE_INT16);

        $value = $address->extract($responsePacket);

        $this->assertEquals(-32768, $value);
    }

    public function testExtractInt16WithCustomEndian()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x01\x80\x00\x00\x03\x00\x04", 3, 33152);
        $address = new ReadRegisterAddress(1, Address::TYPE_INT16, null, null, null, Endian::LITTLE_ENDIAN);

        $value = $address->extract($responsePacket);

        $this->assertEquals(128, $value);
    }

    public function testExtractUint16()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x01\x80\x00\x00\x03\x00\x04", 3, 33152);
        $address = new ReadRegisterAddress(1, Address::TYPE_UINT16);

        $value = $address->extract($responsePacket);

        $this->assertEquals(32768, $value);
    }

    public function testExtractUint16WithCustomEndian()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x01\x80\x00\x00\x03\x00\x04", 3, 33152);
        $address = new ReadRegisterAddress(1, Address::TYPE_UINT16, null, null, null, Endian::LITTLE_ENDIAN);

        $value = $address->extract($responsePacket);

        $this->assertEquals(128, $value);
    }

    public function testExtractUint32()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x01\xFF\xFF\x7F\xFF\x00\x04", 3, 33152);
        $address = new ReadRegisterAddress(1, Address::TYPE_UINT32);

        $value = $address->extract($responsePacket);

        $this->assertEquals(2147483647, $value);
    }

    public function testExtractUint32WithCustomEndian()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x01\xFF\xFF\x7F\xFF\x00\x04", 3, 33152);
        $address = new ReadRegisterAddress(1, Address::TYPE_UINT32, null, null, null, Endian::LITTLE_ENDIAN);

        $value = $address->extract($responsePacket);

        $this->assertEquals(4294967167, $value);
    }

    public function testExtractInt32()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x01\x00\x00\x80\x00\x00\x04", 3, 33152);
        $address = new ReadRegisterAddress(1, Address::TYPE_INT32);

        $value = $address->extract($responsePacket);

        $this->assertEquals(-2147483648, $value);
    }

    public function testExtractInt32WithCustomEndian()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x01\x00\x00\x80\x00\x00\x04", 3, 33152);
        $address = new ReadRegisterAddress(1, Address::TYPE_INT32, null, null, null, Endian::LITTLE_ENDIAN);

        $value = $address->extract($responsePacket);

        $this->assertEquals(128, $value);
    }

    public function testExtractFloat()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x01\xAA\xAB\x3F\x2A\x00\x04", 3, 33152);
        $address = new ReadRegisterAddress(1, Address::TYPE_FLOAT);

        $value = $address->extract($responsePacket);

        $this->assertEqualsWithDelta(0.6666666, $value, 0.0000001);
    }

    public function testExtractFloatWithCustomEndian()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x00\x00\x00\xcd\xcc\xec\x3f", 3, 33152);
        $address = new ReadRegisterAddress(2, Address::TYPE_FLOAT, null, null, null, Endian::LITTLE_ENDIAN);

        $value = $address->extract($responsePacket);

        $this->assertEqualsWithDelta(1.85, $value, 0.0000001);
    }

    public function testExtractDouble()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x4d\x82\x30\x10\xcc\xc3\x41\xc1", 3, 33152);
        $address = new ReadRegisterAddress(0, Address::TYPE_DOUBLE);

        $value = $address->extract($responsePacket);

        $this->assertEqualsWithDelta(597263968.12737, $value, 0.00001);
    }

    public function testExtractDoubleWithCustomEndian()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x82\x4d\x10\x30\xc3\xcc\xc1\x41", 3, 33152);
        $address = new ReadRegisterAddress(0, Address::TYPE_DOUBLE, null, null, null, Endian::LITTLE_ENDIAN);

        $value = $address->extract($responsePacket);

        $this->assertEqualsWithDelta(597263968.12737, $value, 0.00001);
    }

    public function testExtractUInt64()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('32-bit version of PHP');
        }

        $responsePacket = new ReadHoldingRegistersResponse("\x08\xFF\xFF\x7F\xFF\x00\x00\x00\x00", 3, 33152);
        $address = new ReadRegisterAddress(0, Address::TYPE_UINT64);

        $value = $address->extract($responsePacket);

        $this->assertEquals(2147483647, $value);
    }

    public function testExtractUInt64WithCustomEndian()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('32-bit version of PHP');
        }

        $responsePacket = new ReadHoldingRegistersResponse("\x08\xFF\xFF\x7F\xFF\x00\x00\x00\x00", 3, 33152);
        $address = new ReadRegisterAddress(0, Address::TYPE_UINT64, null, null, null, Endian::LITTLE_ENDIAN);

        $value = $address->extract($responsePacket);

        $this->assertEquals(4286578687, $value);
    }

    public function testExtractInt64()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('32-bit version of PHP');
        }

        $responsePacket = new ReadHoldingRegistersResponse("\x08\xFF\xFF\xFF\xFF\xFF\xFF\xFF\xFF", 3, 33152);
        $address = new ReadRegisterAddress(0, Address::TYPE_INT64);

        $value = $address->extract($responsePacket);

        $this->assertEquals(-1, $value);
    }

    public function testExtractInt64WithCustomEndian()
    {
        if (PHP_INT_SIZE === 4) {
            $this->markTestSkipped('32-bit version of PHP');
        }

        $responsePacket = new ReadHoldingRegistersResponse("\x08\x01\x00\x00\x00\x00\x00\x00\x00", 3, 33152);
        $address = new ReadRegisterAddress(0, Address::TYPE_INT64, null, null, null, Endian::LITTLE_ENDIAN);

        $value = $address->extract($responsePacket);

        $this->assertEquals(1, $value);
    }

}
