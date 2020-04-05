<?php

namespace Tests\unit\Composer\Read\Register;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Composer\Read\Register\ReadRegisterAddress;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
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
            'uint32 size should be 2' => ['uint32', 2],
            'uint64 size should be 4' => ['uint64', 4],
        ];
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage Invalid address type given! type: 'byte', address: 1
     */
    public function testInvalidType()
    {
        new ReadRegisterAddress(1, Address::TYPE_BYTE);
    }

    public function testGetName()
    {
        $address = new ReadRegisterAddress(1, Address::TYPE_INT16, 'temp1');
        $this->assertEquals('temp1', $address->getName());
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


    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage test
     */
    public function testExtractWithNoErrorCallback()
    {
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

    public function testExtractUint16()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x01\x80\x00\x00\x03\x00\x04", 3, 33152);
        $address = new ReadRegisterAddress(1, Address::TYPE_UINT16);

        $value = $address->extract($responsePacket);

        $this->assertEquals(32768, $value);
    }

    public function testExtractUint32()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x01\xFF\xFF\x7F\xFF\x00\x04", 3, 33152);
        $address = new ReadRegisterAddress(1, Address::TYPE_UINT32);

        $value = $address->extract($responsePacket);

        $this->assertEquals(2147483647, $value);
    }

    public function testExtractInt32()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x01\x00\x00\x80\x00\x00\x04", 3, 33152);
        $address = new ReadRegisterAddress(1, Address::TYPE_INT32);

        $value = $address->extract($responsePacket);

        $this->assertEquals(-2147483648, $value);
    }

    public function testExtractFloat()
    {
        $responsePacket = new ReadHoldingRegistersResponse("\x08\x00\x01\xAA\xAB\x3F\x2A\x00\x04", 3, 33152);
        $address = new ReadRegisterAddress(1, Address::TYPE_FLOAT);

        $value = $address->extract($responsePacket);

        $this->assertEquals(0.6666666, $value, null, 0.0000001);
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

}
