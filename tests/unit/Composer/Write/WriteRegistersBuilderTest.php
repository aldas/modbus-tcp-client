<?php

namespace Tests\unit\Composer\Write;


use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Composer\Write\Register\WriteRegisterAddress;
use ModbusTcpClient\Composer\Write\WriteRegistersBuilder;
use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleRegistersRequest;
use PHPUnit\Framework\TestCase;

class WriteRegistersBuilderTest extends TestCase
{

    public function testBuildSplitRequestsBecauseOfCapInAddress()
    {
        $requests = WriteRegistersBuilder::newWriteMultipleRegisters('tcp://127.0.0.1:5022')
            ->int16(278, 5)
            ->uint16(280, 4)
            ->build();

        $this->assertCount(2, $requests);
    }

    public function testBuildSplitFailsDueAddressRangeError()
    {
        $this->expectExceptionMessage("Trying to write addresses that seem share their memory range!");
        $this->expectException(InvalidArgumentException::class);

        WriteRegistersBuilder::newWriteMultipleRegisters('tcp://127.0.0.1:5022')
            ->int32(278, 5) // int32 is 2 registers wide - 278 and 279
            ->uint16(279, 4) // so we are trying to write uint16 on same memory space as int32
            ->build();
    }

    public function testBuildSplitRequestTo3()
    {
        $requests = WriteRegistersBuilder::newWriteMultipleRegisters('tcp://127.0.0.1:5022')
            ->int16(278, 5)
            ->uint16(279, 4)
            ->int32(280, 2)
            ->uint32(282, 10)
            // will be split into 2 requests as 1 request can return only range of 124 registers max
            ->int64(450, 12)
            ->uint64(454, 13)
            // will be another request as uri is different for subsequent string register
            ->useUri('tcp://127.0.0.1:5023')
            ->float(270, 1.2)
            ->double(272, 1)
            ->string(276, 'Hello', 10)
            ->build();

        $this->assertCount(3, $requests);

        $writeRequest = $requests[0];
        $this->assertInstanceOf(WriteMultipleRegistersRequest::class, $writeRequest->getRequest());
        $this->assertEquals('tcp://127.0.0.1:5022', $writeRequest->getUri());
        $this->assertCount(4, $writeRequest->getAddresses());

        $writeRequest1 = $requests[1];
        $this->assertInstanceOf(WriteMultipleRegistersRequest::class, $writeRequest1->getRequest());
        $this->assertEquals('tcp://127.0.0.1:5022', $writeRequest1->getUri());
        $this->assertCount(2, $writeRequest1->getAddresses());

        $writeRequest2 = $requests[2];
        $this->assertInstanceOf(WriteMultipleRegistersRequest::class, $writeRequest2->getRequest());
        $this->assertEquals('tcp://127.0.0.1:5023', $writeRequest2->getUri());
        $this->assertCount(3, $writeRequest2->getAddresses());
    }

    public function testBuildAllFromArray()
    {
        $requests = WriteRegistersBuilder::newWriteMultipleRegisters('tcp://127.0.0.1:5022')
            ->allFromArray([
                ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'string', 'value' => 'Søren', 'address' => 0, 'length' => 10],
                // will be split into 2 requests as 1 request can return only range of 124 registers max
                ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'uint16', 'value' => 1, 'address' => 453],
                ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'uint16', 'value' => 1, 'address' => 454],
                // will be another request as uri is different for subsequent string register
                ['uri' => 'tcp://127.0.0.1:5023', 'type' => 'uint16', 'value' => 1, 'address' => 270],
                ['uri' => 'tcp://127.0.0.1:5023', 'type' => 'int16', 'value' => 1, 'address' => 271],
                ['uri' => 'tcp://127.0.0.1:5023', 'type' => 'int32', 'value' => 1, 'address' => 272],
                ['uri' => 'tcp://127.0.0.1:5023', 'type' => 'uint32', 'value' => 1, 'address' => 274],
                ['uri' => 'tcp://127.0.0.1:5023', 'type' => 'uint64', 'value' => 1, 'address' => 276],
                ['uri' => 'tcp://127.0.0.1:5023', 'type' => 'int64', 'value' => 1, 'address' => 280],
                ['uri' => 'tcp://127.0.0.1:5023', 'type' => 'float', 'value' => 1, 'address' => 284],
                ['uri' => 'tcp://127.0.0.1:5023', 'type' => 'double', 'value' => 1, 'address' => 286],
            ])
            ->build();

        $this->assertCount(3, $requests);

        $this->assertCount(1, $requests[0]->getAddresses());
        $this->assertCount(2, $requests[1]->getAddresses());
        $this->assertCount(8, $requests[2]->getAddresses());
    }

    public function testBuildAllFromArrayUsingObject()
    {
        $requests = WriteRegistersBuilder::newWriteMultipleRegisters('tcp://127.0.0.1:5022')
            ->allFromArray([new WriteRegisterAddress(256, Address::TYPE_INT32, 1)])->build();

        $this->assertCount(1, $requests);
        $this->assertCount(1, $requests[0]->getAddresses());
    }

    public function testBuildStringMissingLength()
    {
        $this->expectExceptionMessage("missing length for string address");
        $this->expectException(InvalidArgumentException::class);

        WriteRegistersBuilder::newWriteMultipleRegisters('tcp://127.0.0.1:5022')
            ->fromArray([
                'uri' => 'tcp://127.0.0.1:5022',
                'type' => 'string',
                'value' => 'Søren',
                'address' => 256,
            ])->build();
    }

    public function testBuildgMissingAddress()
    {
        $this->expectExceptionMessage("empty address given");
        $this->expectException(InvalidArgumentException::class);

        WriteRegistersBuilder::newWriteMultipleRegisters('tcp://127.0.0.1:5022')
            ->fromArray([
                'uri' => 'tcp://127.0.0.1:5022',
                'type' => 'int16',
                'value' => 1,
            ])->build();
    }

    public function testBuildMissingType()
    {
        $this->expectExceptionMessage("empty or unknown type for address given");
        $this->expectException(InvalidArgumentException::class);

        WriteRegistersBuilder::newWriteMultipleRegisters('tcp://127.0.0.1:5022')
            ->fromArray([
                'uri' => 'tcp://127.0.0.1:5022',
                'value' => 1,
                'address' => 256,
            ])->build();
    }

    public function testBuildMissingValue()
    {
        $this->expectExceptionMessage("value missing");
        $this->expectException(InvalidArgumentException::class);

        WriteRegistersBuilder::newWriteMultipleRegisters('tcp://127.0.0.1:5022')
            ->fromArray([
                'uri' => 'tcp://127.0.0.1:5022',
                'type' => 'int16',
                'address' => 256,
            ])->build();
    }

    public function testBuildBitIsNotAllowed()
    {
        $this->expectExceptionMessage("writing bit/byte through register is not supported as 1 word is 2 bytes so we are touching more memory than needed");
        $this->expectException(InvalidArgumentException::class);

        WriteRegistersBuilder::newWriteMultipleRegisters('tcp://127.0.0.1:5022')
            ->fromArray([
                'uri' => 'tcp://127.0.0.1:5022',
                'type' => 'bit',
                'value' => 1,
                'address' => 256,
            ])->build();
    }

    public function testCanNotAddWithoutUri()
    {
        $this->expectExceptionMessage("uri not set");
        $this->expectException(InvalidArgumentException::class);

        WriteRegistersBuilder::newWriteMultipleRegisters()
            ->int64(278, 5)
            ->build();
    }

    public function testCanNotSetEmptyUri()
    {
        $this->expectExceptionMessage("uri can not be empty value");
        $this->expectException(InvalidArgumentException::class);

        WriteRegistersBuilder::newWriteMultipleRegisters()
            ->useUri('')
            ->build();
    }

    /**
     * @dataProvider typesProvider
     */
    public function testAddressTypes($type, $value, $size, $length)
    {
        $requests = WriteRegistersBuilder::newWriteMultipleRegisters('tcp://127.0.0.1:5022')
            ->$type(280, $value, $length)
            ->build();

        $this->assertCount(1, $requests);

        $addresses = $requests[0]->getAddresses();
        $this->assertCount(1, $addresses);

        /** @var WriteRegisterAddress $address */
        $address = $addresses[0];
        $this->assertEquals($type, $address->getType());
        $this->assertEquals(280, $address->getAddress());
        $this->assertEquals($value, $address->getValue());
        $this->assertEquals($size, $address->getSize());
    }

    public function typesProvider(): array
    {
        return [
            'add int16' => ['int16', 1, 1, null],
            'add uint16' => ['uint16', 1, 1, null],
            'add int32 ' => ['int32', 1, 2, null],
            'add uint32' => ['uint32', 1, 2, null],
            'add float' => ['float', 1, 2, null],
            'add int64' => ['int64', 1, 4, null],
            'add uint64' => ['uint64', 1, 4, null],
            'add string' => ['string', 'Søren', 3, 6],
        ];
    }

    public function testIsNotEmptyTrue()
    {
        $builder = WriteRegistersBuilder::newWriteMultipleRegisters('tcp://127.0.0.1:5022')
            ->int32(278, 5);

        $this->assertTrue($builder->isNotEmpty());
    }

    public function testIsNotEmptyFalse()
    {
        $builder = WriteRegistersBuilder::newWriteMultipleRegisters('tcp://127.0.0.1:5022');

        $this->assertFalse($builder->isNotEmpty());
    }

}
