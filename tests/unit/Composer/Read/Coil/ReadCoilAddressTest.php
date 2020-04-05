<?php

namespace Tests\unit\Composer\Read\Coil;

use ModbusTcpClient\Composer\Read\Coil\ReadCoilAddress;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsResponse;
use PHPUnit\Framework\TestCase;

class ReadCoilAddressTest extends TestCase
{

    public function toExtractProvider()
    {
        return [
            'extract true from 1 bit' => ["\x01\x01", 0, true],
            'extract false from 2 bit' => ["\x01\x01", 1, false],
            'extract true from 2 bit' => ["\x01\x02", 1, true],
        ];
    }

    /**
     * @dataProvider toExtractProvider
     */
    public function testExtract(string $payload, int $address, bool $expected)
    {
        $responsePacket = new ReadCoilsResponse($payload, 3, 33152);
        $address = new ReadCoilAddress($address, 'hello');

        $value = $address->extract($responsePacket);

        $this->assertEquals($expected, $value);
    }

    public function testExtractResponse()
    {
        $responsePacket = new ReadCoilsResponse("\x01\x02", 3, 33152);
        $address = new ReadCoilAddress(1, 'hello');

        $this->assertEquals(1, $address->getAddress());
        $this->assertEquals('hello', $address->getName());
        $this->assertEquals(1, $address->getSize());

        $value = $address->extract($responsePacket);
        $this->assertEquals(true, $value);
    }

    public function testGetNameWithAddress()
    {
        $address = new ReadCoilAddress(1);

        $this->assertEquals('1', $address->getName());
    }

    public function testExtractWithCallback()
    {
        $responsePacket = new ReadCoilsResponse("\x01\x02", 3, 33152);
        $address = new ReadCoilAddress(1, 'hello', function ($result) {
            return false;
        });

        $value = $address->extract($responsePacket);
        $this->assertEquals(false, $value);
    }

    public function testExtractWithErrorCallback()
    {
        $responsePacket = new ReadCoilsResponse("\x01\x02", 3, 33152);
        $address = new ReadCoilAddress(
            1,
            'hello',
            function ($result) {
                throw new \RuntimeException('test');
            },
            function ($exception) {
                if ($exception instanceof \RuntimeException) {
                    return false;
                }
                throw new \RuntimeException('fail');
            }
        );

        $value = $address->extract($responsePacket);
        $this->assertEquals(false, $value);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMessage test
     */
    public function testExtractWithNoErrorCallback()
    {
        $responsePacket = new ReadCoilsResponse("\x01\x02", 3, 33152);
        $address = new ReadCoilAddress(
            1,
            'hello',
            function () {
                throw new \RuntimeException('test');
            }
        );

        $address->extract($responsePacket);
    }
}
