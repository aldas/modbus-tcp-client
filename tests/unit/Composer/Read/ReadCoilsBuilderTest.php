<?php

namespace Tests\unit\Composer\Read;

use ModbusTcpClient\Composer\AddressSplitter;
use ModbusTcpClient\Composer\Read\Coil\ReadCoilAddress;
use ModbusTcpClient\Composer\Read\ReadCoilsBuilder;
use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputDiscretesRequest;
use PHPUnit\Framework\TestCase;

class ReadCoilsBuilderTest extends TestCase
{
    public function testBuildSplitRequestTo3()
    {
        $requests = ReadCoilsBuilder::newReadCoils('tcp://127.0.0.1:5022')
            ->coil(278, 'dirchange1_status_di')
            // will be split into 2 requests as 1 request can return only range of MAX_COILS_PER_MODBUS_REQUEST coils max
            ->coil(278 + AddressSplitter::MAX_COILS_PER_MODBUS_REQUEST, 'gen1_fuel_rate_wo')
            // will be another request as uri is different for subsequent string register
            ->useUri('tcp://127.0.0.1:5023')
            ->coil(278, 'me_dirchange1_status_di')
            ->build();

        $this->assertCount(3, $requests);

        $readRequest = $requests[0];
        $this->assertInstanceOf(ReadCoilsRequest::class, $readRequest->getRequest());
        $this->assertEquals('tcp://127.0.0.1:5022', $readRequest->getUri());
        $this->assertCount(1, $readRequest->getAddresses());

        $readRequest1 = $requests[1];
        $this->assertInstanceOf(ReadCoilsRequest::class, $readRequest1->getRequest());
        $this->assertEquals('tcp://127.0.0.1:5022', $readRequest1->getUri());
        $this->assertCount(1, $readRequest1->getAddresses());

        $readRequest2 = $requests[2];
        $this->assertInstanceOf(ReadCoilsRequest::class, $readRequest2->getRequest());
        $this->assertEquals('tcp://127.0.0.1:5023', $readRequest2->getUri());
        $this->assertCount(1, $readRequest2->getAddresses());
    }

    public function unaddressableRangeProvider(): array
    {
        return [
            'ok, empty range' => [[], [3], null],
            'ok, split to 2 requests because range is between 110 and 111' => [[[90, 99], [105, 110]], [1, 2], null],
            'nok, address fall directly to range beginning' => [[[112, 113]], [], 'address at 112 with size 1 overlaps unaddressable range'],
            'nok, address fall directly to range center' => [[[99, 101]], [], 'address at 100 with size 1 overlaps unaddressable range'],
            'nok, address fall directly to range end' => [[[99, 100]], [], 'address at 100 with size 1 overlaps unaddressable range'],
            'nok, address fall directly to range' => [[[100]], [], 'address at 100 with size 1 overlaps unaddressable range'],
            'nok, invalid range' => [[[1, 2, 3]], [], 'Range can only be created from array with 1 or 2 elements'],
        ];
    }

    /**
     * @dataProvider unaddressableRangeProvider
     */
    public function testBuildSplitRequestUnaddressableRange($unaddressable, $expectRequestCounts, $expectExceptionMessage)
    {
        if ($expectExceptionMessage !== null) {
            $this->expectExceptionMessage($expectExceptionMessage);
        }

        $requests = ReadCoilsBuilder::newReadInputDiscretes('tcp://127.0.0.1:5022')
            ->unaddressableRanges($unaddressable)
            ->coil(100, 'me_dirchange1_status_di')
            ->coil(111, 'me_dirchange2_status_di')
            ->coil(112, 'me_dirchange3_status_di')
            ->build();

        $this->assertCount(count($expectRequestCounts), $requests);
        foreach ($expectRequestCounts as $idx => $expectedCount) {
            $readRequest = $requests[$idx];
            $this->assertInstanceOf(ReadInputDiscretesRequest::class, $readRequest->getRequest());
            $this->assertCount($expectedCount, $readRequest->getAddresses());
        }
    }

    public function testCanNotUnaddressableRangesWithoutUri()
    {
        $this->expectExceptionMessage("unaddressable ranges can not be added when uri is empty");
        $this->expectException(InvalidArgumentException::class);

        ReadCoilsBuilder::newReadCoils()
            ->unaddressableRanges([[111]])
            ->coil(278, 'dirchange1_status')
            ->build();
    }

    public function testBuildReadInputDiscretes()
    {
        $requests = ReadCoilsBuilder::newReadInputDiscretes('tcp://127.0.0.1:5022')
            ->coil(278, 'dirchange1_status_di')
            // will be split into 2 requests as 1 request can return only range of MAX_COILS_PER_MODBUS_REQUEST coils max
            ->coil(278 + AddressSplitter::MAX_COILS_PER_MODBUS_REQUEST, 'gen1_fuel_rate_wo')
            // will be another request as uri is different for subsequent string register
            ->useUri('tcp://127.0.0.1:5023')
            ->coil(278, 'me_dirchange1_status_di')
            ->build();

        $this->assertCount(3, $requests);

        $readRequest = $requests[0];
        $this->assertInstanceOf(ReadInputDiscretesRequest::class, $readRequest->getRequest());
        $this->assertEquals('tcp://127.0.0.1:5022', $readRequest->getUri());
        $this->assertCount(1, $readRequest->getAddresses());

        $readRequest1 = $requests[1];
        $this->assertInstanceOf(ReadInputDiscretesRequest::class, $readRequest1->getRequest());
        $this->assertEquals('tcp://127.0.0.1:5022', $readRequest1->getUri());
        $this->assertCount(1, $readRequest1->getAddresses());

        $readRequest2 = $requests[2];
        $this->assertInstanceOf(ReadInputDiscretesRequest::class, $readRequest2->getRequest());
        $this->assertEquals('tcp://127.0.0.1:5023', $readRequest2->getUri());
        $this->assertCount(1, $readRequest2->getAddresses());
    }

    public function testBuildAllFromArray()
    {
        $requests = ReadCoilsBuilder::newReadCoils('tcp://127.0.0.1:5022')
            ->allFromArray([
                ['uri' => 'tcp://127.0.0.1:5022', 'address' => 276, 'name' => 'dirchange1_status'],
                ['uri' => 'tcp://127.0.0.1:5022', 'address' => 277, 'name' => 'dirchange2_status'],
                ['uri' => 'tcp://127.0.0.1:5022', 'address' => 278, 'name' => 'dirchange3_status'],
                // will be split into 2 requests as 1 request can return only range of MAX_COILS_PER_MODBUS_REQUEST coils max
                ['uri' => 'tcp://127.0.0.1:5022', 'address' => 278 + AddressSplitter::MAX_COILS_PER_MODBUS_REQUEST, 'name' => 'gen1_fuel_rate_wo'],
                // will be another request as uri is different for subsequent string register
                ['uri' => 'tcp://127.0.0.1:5023', 'address' => 270, 'name' => 'room7_temp_wo'],
                new ReadCoilAddress(271, 'room8_temp_wo'),
            ])
            ->build();

        $this->assertCount(3, $requests);

        $this->assertCount(3, $requests[0]->getAddresses());
        $this->assertCount(1, $requests[1]->getAddresses());
        $this->assertCount(2, $requests[2]->getAddresses());
    }

    public function testCanNotAddWithoutUri()
    {
        $this->expectExceptionMessage("uri not set");
        $this->expectException(InvalidArgumentException::class);

        ReadCoilsBuilder::newReadCoils()
            ->coil(278, 'dirchange1_status')
            ->build();
    }

    public function testCanNotSetEmptyUri()
    {
        $this->expectExceptionMessage("uri can not be empty value");
        $this->expectException(InvalidArgumentException::class);

        ReadCoilsBuilder::newReadCoils()
            ->useUri('')
            ->build();
    }

    public function testEmptyAddress()
    {
        $this->expectExceptionMessage("empty address given");
        $this->expectException(InvalidArgumentException::class);

        ReadCoilsBuilder::newReadCoils('tcp://127.0.0.1:5022')
            ->fromArray([
                'uri' => 'tcp://127.0.0.1:5022',
                'name' => 'hello',
            ])->build();
    }

    public function testInvalidCallback()
    {
        $this->expectExceptionMessage("callback must be a an anonymous function");
        $this->expectException(InvalidArgumentException::class);

        ReadCoilsBuilder::newReadCoils('tcp://127.0.0.1:5022')
            ->fromArray([
                'uri' => 'tcp://127.0.0.1:5022',
                'address' => 1,
                'name' => 'hello',
                'callback' => 'not a function',
            ])->build();
    }

    public function testInvalidErrorCallback()
    {
        $this->expectExceptionMessage("error callback must be a an anonymous function");
        $this->expectException(InvalidArgumentException::class);

        ReadCoilsBuilder::newReadCoils('tcp://127.0.0.1:5022')
            ->fromArray([
                'uri' => 'tcp://127.0.0.1:5022',
                'address' => 1,
                'name' => 'hello',
                'errorCallback' => 'not a function',
            ])->build();
    }

    public function testIsEmpty()
    {
        $builder = ReadCoilsBuilder::newReadInputDiscretes('tcp://127.0.0.1:5022');

        $this->assertEquals(false, $builder->isNotEmpty());
        $this->assertCount(0, $builder->build());
    }
}
