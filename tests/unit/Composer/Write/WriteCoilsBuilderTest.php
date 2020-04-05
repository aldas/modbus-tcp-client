<?php

namespace Tests\unit\Composer\Write;


use ModbusTcpClient\Composer\AddressSplitter;
use ModbusTcpClient\Composer\Write\Coil\WriteCoilAddress;
use ModbusTcpClient\Composer\Write\WriteCoilsBuilder;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleCoilsRequest;
use PHPUnit\Framework\TestCase;

class WriteCoilsBuilderTest extends TestCase
{

    public function testBuildSplitRequestsBecauseOfCapInAddress()
    {
        $requests = WriteCoilsBuilder::newWriteMultipleCoils('tcp://127.0.0.1:5022')
            ->coil(278, true)
            ->coil(280, false)
            ->build();

        $this->assertCount(2, $requests);
    }

    public function testBuildSplitRequestTo3()
    {
        $requests = WriteCoilsBuilder::newWriteMultipleCoils('tcp://127.0.0.1:5022')
            ->coil(278, true)
            ->coil(279, true)
            ->coil(280, true)
            ->coil(281, false)
            // will be split into 2 requests as 1 request can return only range of 124 registers max
            ->coil(450 + AddressSplitter::MAX_COILS_PER_MODBUS_REQUEST, true)
            ->coil(451 + AddressSplitter::MAX_COILS_PER_MODBUS_REQUEST, true)
            // will be another request as uri is different for subsequent string register
            ->useUri('tcp://127.0.0.1:5023')
            ->coil(270, true)
            ->coil(271, true)
            ->build();

        $this->assertCount(3, $requests);

        $writeRequest = $requests[0];
        $this->assertInstanceOf(WriteMultipleCoilsRequest::class, $writeRequest->getRequest());
        $this->assertEquals('tcp://127.0.0.1:5022', $writeRequest->getUri());
        $this->assertCount(4, $writeRequest->getAddresses());

        $writeRequest1 = $requests[1];
        $this->assertInstanceOf(WriteMultipleCoilsRequest::class, $writeRequest1->getRequest());
        $this->assertEquals('tcp://127.0.0.1:5022', $writeRequest1->getUri());
        $this->assertCount(2, $writeRequest1->getAddresses());

        $writeRequest2 = $requests[2];
        $this->assertInstanceOf(WriteMultipleCoilsRequest::class, $writeRequest2->getRequest());
        $this->assertEquals('tcp://127.0.0.1:5023', $writeRequest2->getUri());
        $this->assertCount(2, $writeRequest2->getAddresses());
    }

    public function testBuildAllFromArray()
    {
        $requests = WriteCoilsBuilder::newWriteMultipleCoils('tcp://127.0.0.1:5022')
            ->allFromArray([
                ['uri' => 'tcp://127.0.0.1:5022', 'value' => true, 'address' => 0],
                // will be split into 2 requests as 1 request can return only range of 2048 coils max
                ['uri' => 'tcp://127.0.0.1:5022', 'value' => true, 'address' => 453],
                ['uri' => 'tcp://127.0.0.1:5022', 'value' => true, 'address' => 454],
                // will be another request as uri is different for subsequent coils
                ['uri' => 'tcp://127.0.0.1:5023', 'value' => true, 'address' => 270],
                ['uri' => 'tcp://127.0.0.1:5023', 'value' => true, 'address' => 271],
                ['uri' => 'tcp://127.0.0.1:5023', 'value' => false, 'address' => 272],
                ['uri' => 'tcp://127.0.0.1:5023', 'value' => true, 'address' => 273],
                ['uri' => 'tcp://127.0.0.1:5023', 'value' => true, 'address' => 274],
                ['uri' => 'tcp://127.0.0.1:5023', 'value' => true, 'address' => 275],
                ['uri' => 'tcp://127.0.0.1:5023', 'value' => true, 'address' => 276],
            ])
            ->build();

        $this->assertCount(3, $requests);

        $this->assertCount(1, $requests[0]->getAddresses());
        $this->assertCount(2, $requests[1]->getAddresses());
        $this->assertCount(7, $requests[2]->getAddresses());
    }

    public function testBuildAllFromArrayUsingObject()
    {
        $requests = WriteCoilsBuilder::newWriteMultipleCoils('tcp://127.0.0.1:5022')
            ->allFromArray([new WriteCoilAddress(256, true)])->build();

        $this->assertCount(1, $requests);
        $this->assertCount(1, $requests[0]->getAddresses());
    }

    public function testBuildAllowsForOverlappingCoils()
    {
        $requests = WriteCoilsBuilder::newWriteMultipleCoils('tcp://127.0.0.1:5022')
            ->allFromArray([
                new WriteCoilAddress(256, true),
                new WriteCoilAddress(256, false),
            ])->build();

        $this->assertCount(1, $requests);
        $this->assertCount(1, $requests[0]->getAddresses());
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage empty address given
     */
    public function testBuildgMissingAddress()
    {
        WriteCoilsBuilder::newWriteMultipleCoils('tcp://127.0.0.1:5022')
            ->fromArray([
                'uri' => 'tcp://127.0.0.1:5022',
                'value' => true,
            ])->build();
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage value missing
     */
    public function testBuildMissingValue()
    {
        WriteCoilsBuilder::newWriteMultipleCoils('tcp://127.0.0.1:5022')
            ->fromArray([
                'uri' => 'tcp://127.0.0.1:5022',
                'address' => 256,
            ])->build();
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage uri not set
     */
    public function testCanNotAddWithoutUri()
    {
        WriteCoilsBuilder::newWriteMultipleCoils()
            ->coil(278, true)
            ->build();
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage uri can not be empty value
     */
    public function testCanNotSetEmptyUri()
    {
        WriteCoilsBuilder::newWriteMultipleCoils()
            ->useUri('')
            ->build();
    }

    public function testIsNotEmptyTrue()
    {
        $builder = WriteCoilsBuilder::newWriteMultipleCoils('tcp://127.0.0.1:5022')
            ->coil(278, true);

        $this->assertTrue($builder->isNotEmpty());
    }

    public function testIsNotEmptyFalse()
    {
        $builder = WriteCoilsBuilder::newWriteMultipleCoils('tcp://127.0.0.1:5022');

        $this->assertFalse($builder->isNotEmpty());
    }

}
