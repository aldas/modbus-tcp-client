<?php

namespace Tests\unit\Composer\Read;

use ModbusTcpClient\Composer\Address;
use ModbusTcpClient\Composer\Read\ReadRegistersBuilder;
use ModbusTcpClient\Composer\Read\Register\BitReadRegisterAddress;
use ModbusTcpClient\Composer\Read\Register\ByteReadRegisterAddress;
use ModbusTcpClient\Composer\Read\Register\ReadRegisterAddress;
use ModbusTcpClient\Composer\Read\Register\StringReadRegisterAddress;
use ModbusTcpClient\Composer\RegisterAddress;
use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersRequest;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class ReadRegistersBuilderTest extends TestCase
{
    public function testBuildSplitRequestTo3()
    {
        $requests = ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->bit(278, 5, 'dirchange1_status')
            ->bit(278, 4, 'dirchange2_status')
            ->byte(256, true, 'username_first_char')
            ->string(256, 10, 'username')
            // will be split into 2 requests as 1 request can return only range of 124 registers max
            ->uint16(453, 'gen1_fuel_rate_wo')
            ->uint16(454, 'gen2_fuel_rate_wo')
            // will be another request as uri is different for subsequent string register
            ->useUri('tcp://127.0.0.1:5023')
            ->int16(270, 'room7_temp_wo')
            ->build();

        $this->assertCount(3, $requests);

        $readRequest = $requests[0];
        $this->assertInstanceOf(ReadHoldingRegistersRequest::class, $readRequest->getRequest());
        $this->assertEquals('tcp://127.0.0.1:5022', $readRequest->getUri());
        $this->assertCount(4, $readRequest->getAddresses());

        $readRequest1 = $requests[1];
        $this->assertInstanceOf(ReadHoldingRegistersRequest::class, $readRequest1->getRequest());
        $this->assertEquals('tcp://127.0.0.1:5022', $readRequest1->getUri());
        $this->assertCount(2, $readRequest1->getAddresses());

        $readRequest2 = $requests[2];
        $this->assertInstanceOf(ReadHoldingRegistersRequest::class, $readRequest2->getRequest());
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

        $requests = ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->unaddressableRanges($unaddressable)
            ->uint16(100, 'gen1_fuel_rate_wo')
            ->uint16(111, 'gen2_fuel_rate_wo')
            ->uint16(112, 'gen3_fuel_rate_wo')
            ->build();

        $this->assertCount(count($expectRequestCounts), $requests);
        foreach ($expectRequestCounts as $idx => $expectedCount) {
            $readRequest = $requests[$idx];
            $this->assertInstanceOf(ReadHoldingRegistersRequest::class, $readRequest->getRequest());
            $this->assertCount($expectedCount, $readRequest->getAddresses());
        }
    }

    public function testCanNotUnaddressableRangesWithoutUri()
    {
        $this->expectExceptionMessage("unaddressable ranges can not be added when uri is empty");
        $this->expectException(InvalidArgumentException::class);

        ReadRegistersBuilder::newReadHoldingRegisters()
            ->unaddressableRanges([[111]])
            ->uint16(100, 'gen1_fuel_rate_wo')
            ->build();
    }

    public function testBuildAllFromArray()
    {
        $requests = ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->allFromArray([
                ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'bit', 'address' => 278, 'bit' => 5, 'name' => 'dirchange1_status'],
                ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'bit', 'address' => 278, 'bit' => 4, 'name' => 'dirchange2_status'],
                ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'byte', 'address' => 256, 'firstByte' => true, 'name' => 'username_first_char'],
                ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'string', 'address' => 256, 'length' => 10, 'name' => 'username'],
                // will be split into 2 requests as 1 request can return only range of 124 registers max
                ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'uint16', 'address' => 0, 'name' => 'gen1_fuel_rate_wo'],
                ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'uint16', 'address' => 1, 'name' => 'gen2_fuel_rate_wo'],
                // will be another request as uri is different for subsequent string register
                ['uri' => 'tcp://127.0.0.1:5023', 'type' => 'uint16', 'address' => 270, 'name' => 'room7_temp_wo'],
                ['uri' => 'tcp://127.0.0.1:5023', 'type' => 'int16', 'address' => 270, 'name' => 'room8_temp_wo'],
                ['uri' => 'tcp://127.0.0.1:5023', 'type' => 'int32', 'address' => 271, 'name' => 'int32_wo'],
                ['uri' => 'tcp://127.0.0.1:5023', 'type' => 'uint32', 'address' => 271, 'name' => 'uint32_wo'],
                ['uri' => 'tcp://127.0.0.1:5023', 'type' => 'uint64', 'address' => 271, 'name' => 'uint64_wo'],
                ['uri' => 'tcp://127.0.0.1:5023', 'type' => 'int64', 'address' => 271, 'name' => 'int64_wo'],
                ['uri' => 'tcp://127.0.0.1:5023', 'type' => 'float', 'address' => 271, 'name' => 'float_wo'],
                ['uri' => 'tcp://127.0.0.1:5023', 'type' => 'double', 'address' => 271, 'name' => 'double_wo'],
            ])
            ->build();

        $this->assertCount(3, $requests);

        $this->assertCount(2, $requests[0]->getAddresses());
        $this->assertCount(4, $requests[1]->getAddresses());
        $this->assertCount(8, $requests[2]->getAddresses());
    }

    public function testBuildAllFromArraySingle()
    {
        $requests = ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->allFromArray([
                [
                    'uri' => 'tcp://127.0.0.1:5023',
                    'type' => 'uint16',
                    'address' => 270,
                    'name' => 'room7_temp_wo',
                    'endian' => Endian::LITTLE_ENDIAN
                ],
            ])
            ->build();

        $this->assertCount(1, $requests);

        $addresses = $requests[0]->getAddresses();
        $this->assertCount(1, $addresses);

        $address = $addresses[0];
        $this->assertEquals(Address::TYPE_UINT16, $address->getType());
        $this->assertEquals(270, $address->getAddress());
        $this->assertEquals('room7_temp_wo', $address->getName());
        $this->assertEquals(1, $address->getSize());
        $this->assertEquals(Endian::LITTLE_ENDIAN, $address->getEndian());
    }

    public function testBuildAllFromArrayWithReadAddress()
    {
        $requests = ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->allFromArray([
                ['uri' => 'tcp://127.0.0.1:5022', 'type' => 'bit', 'address' => 278, 'bit' => 5, 'name' => 'dirchange1_status'],
                new ReadRegisterAddress(271, Address::TYPE_FLOAT, 'float_wo'),
            ])
            ->build();

        $this->assertCount(1, $requests);
        $this->assertCount(2, $requests[0]->getAddresses());
    }

    public function testBuildNewReadInputRegisters()
    {
        $requests = ReadRegistersBuilder::newReadInputRegisters('tcp://127.0.0.1:5022')
            ->bit(278, 5, 'dirchange1_status')
            ->build();

        $this->assertCount(1, $requests);

        $readRequest = $requests[0];
        $this->assertInstanceOf(ReadInputRegistersRequest::class, $readRequest->getRequest());
        $this->assertCount(1, $readRequest->getAddresses());
    }

    public function testCanNotAddWithoutUri()
    {
        $this->expectExceptionMessage("uri not set");
        $this->expectException(InvalidArgumentException::class);

        ReadRegistersBuilder::newReadHoldingRegisters()
            ->bit(278, 5, 'dirchange1_status')
            ->build();
    }

    public function testCanNotSetEmptyUri()
    {
        $this->expectExceptionMessage("uri can not be empty value");
        $this->expectException(InvalidArgumentException::class);

        ReadRegistersBuilder::newReadHoldingRegisters()
            ->useUri('')
            ->build();
    }

    public function testBuildStringMissingLength()
    {
        $this->expectExceptionMessage("missing length for string address");
        $this->expectException(InvalidArgumentException::class);

        ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->fromArray([
                'uri' => 'tcp://127.0.0.1:5022',
                'type' => 'string',
                'address' => 256,
            ])->build();
    }

    public function testBuildInvalidCallback()
    {
        $this->expectExceptionMessage("callback must be a an anonymous function");
        $this->expectException(InvalidArgumentException::class);

        ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->fromArray([
                'uri' => 'tcp://127.0.0.1:5022',
                'address' => 256,
                'type' => 'int16',
                'callback' => 'echo'
            ])->build();
    }

    public function testBuildgInvalidErrorCallback()
    {
        $this->expectExceptionMessage("error callback must be a an anonymous function");
        $this->expectException(InvalidArgumentException::class);

        ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->fromArray([
                'uri' => 'tcp://127.0.0.1:5022',
                'address' => 256,
                'type' => 'int16',
                'errorCallback' => 'echo'
            ])->build();
    }

    public function testBuildgMissingAddress()
    {
        $this->expectExceptionMessage("empty address given");
        $this->expectException(InvalidArgumentException::class);

        ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->fromArray([
                'uri' => 'tcp://127.0.0.1:5022',
                'type' => 'int16',
            ])->build();
    }

    public function testBuildMissingType()
    {
        $this->expectExceptionMessage("empty or unknown type for address given");
        $this->expectException(InvalidArgumentException::class);

        ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->fromArray([
                'uri' => 'tcp://127.0.0.1:5022',
                'address' => 256,
            ])->build();
    }

    public function testAddBit()
    {
        $requests = ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->bit(278, 5, 'dirchange1_status')
            ->build();

        $this->assertCount(1, $requests);

        $addresses = $requests[0]->getAddresses();
        $this->assertCount(1, $addresses);

        /** @var BitReadRegisterAddress $address */
        $address = $addresses[0];
        $this->assertEquals(Address::TYPE_BIT, $address->getType());
        $this->assertEquals(278, $address->getAddress());
        $this->assertEquals(5, $address->getBit());
        $this->assertEquals('dirchange1_status', $address->getName());
        $this->assertEquals(1, $address->getSize());
    }

    public function testBitNumberOverflow()
    {
        $this->expectExceptionMessage("Invalid bit number in for register given! nthBit: '16', address: 280");
        $this->expectException(InvalidArgumentException::class);

        ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->bit(280, 16, 'some_address')
            ->build();
    }

    public function testBitNumberUnderflow()
    {
        $this->expectExceptionMessage("Invalid bit number in for register given! nthBit: '-1', address: 280");
        $this->expectException(InvalidArgumentException::class);

        ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->bit(280, -1, 'some_address')
            ->build();
    }

    public function testAddByte()
    {
        $requests = ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->byte(279, true, 'direction')
            ->build();

        $this->assertCount(1, $requests);

        $addresses = $requests[0]->getAddresses();
        $this->assertCount(1, $addresses);

        /** @var ByteReadRegisterAddress $address */
        $address = $addresses[0];
        $this->assertEquals(Address::TYPE_BYTE, $address->getType());
        $this->assertEquals(279, $address->getAddress());
        $this->assertEquals(true, $address->isFirstByte());
        $this->assertEquals('direction', $address->getName());
        $this->assertEquals(1, $address->getSize());
    }

    public function testAddString()
    {
        $requests = ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->string(280, 10, 'username', null, null, Endian::LITTLE_ENDIAN)
            ->build();

        $this->assertCount(1, $requests);

        $addresses = $requests[0]->getAddresses();
        $this->assertCount(1, $addresses);

        /** @var StringReadRegisterAddress $address */
        $address = $addresses[0];
        $this->assertEquals(Address::TYPE_STRING, $address->getType());
        $this->assertEquals(280, $address->getAddress());
        $this->assertEquals('username', $address->getName());
        $this->assertEquals(5, $address->getSize());
        $this->assertEquals(Endian::LITTLE_ENDIAN, $address->getEndian());
    }

    public function testStringTooLong()
    {
        $this->expectExceptionMessage("Out of range string length for given! length: '229', address: 280");
        $this->expectException(InvalidArgumentException::class);

        ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->string(280, 229, 'some_address')
            ->build();
    }

    /**
     * @dataProvider typesProvider
     */
    public function testAddressTypes($type, $size)
    {
        $requests = ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->$type(280, 'some_address')
            ->build();

        $this->assertCount(1, $requests);

        $addresses = $requests[0]->getAddresses();
        $this->assertCount(1, $addresses);

        /** @var Address $address */
        $address = $addresses[0];
        $this->assertEquals($type, $address->getType());
        $this->assertEquals(280, $address->getAddress());
        $this->assertEquals('some_address', $address->getName());
        $this->assertEquals($size, $address->getSize());
    }

    public function typesProvider(): array
    {
        return [
            'add int16' => ['int16', 1],
            'add uint16' => ['uint16', 1],
            'add int32 ' => ['int32', 2],
            'add uint32' => ['uint32', 2],
            'add float' => ['float', 2],
            'add double' => ['double', 4],
            'add uint64' => ['uint64', 4],
        ];
    }

    /**
     * @dataProvider typesWithEndianProvider
     */
    public function testAddressTypesWithEndian($type, $size, $endian)
    {
        $requests = ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->$type(280, 'some_address', null, null, $endian)
            ->build();

        $this->assertCount(1, $requests);

        $addresses = $requests[0]->getAddresses();
        $this->assertCount(1, $addresses);

        /** @var RegisterAddress $address */
        $address = $addresses[0];
        $this->assertEquals($type, $address->getType());
        $this->assertEquals(280, $address->getAddress());
        $this->assertEquals('some_address', $address->getName());
        $this->assertEquals($size, $address->getSize());
        $this->assertEquals($endian, $address->getEndian());
    }

    public function typesWithEndianProvider(): array
    {
        return [
            'add int16' => ['int16', 1, Endian::LITTLE_ENDIAN],
            'add uint16' => ['uint16', 1, Endian::LITTLE_ENDIAN],
            'add int32 ' => ['int32', 2, Endian::LITTLE_ENDIAN],
            'add uint32' => ['uint32', 2, Endian::LITTLE_ENDIAN],
            'add float' => ['float', 2, Endian::LITTLE_ENDIAN],
            'add double' => ['double', 4, Endian::LITTLE_ENDIAN],
            'add uint64' => ['uint64', 4, Endian::LOW_WORD_FIRST],
        ];
    }

    public function testIsNotEmptyTrue()
    {
        $builder = ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->bit(278, 5, 'dirchange1_status');

        $this->assertTrue($builder->isNotEmpty());
    }

    public function testIsNotEmptyFalse()
    {
        $builder = ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022');

        $this->assertFalse($builder->isNotEmpty());
    }

    public function test0Address()
    {
        $requests = ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022')
            ->int64(0, 'realtime')
            ->int32(4, 'sys time')
            ->build();

        $this->assertCount(1, $requests);

        $request = $requests[0]->getRequest();

        $this->assertEquals(0, $request->getStartAddress());
        $this->assertEquals(6, $request->getQuantity());

        $addresses = $requests[0]->getAddresses();
        $this->assertCount(2, $addresses);

        /** @var ReadRegisterAddress $address */
        $address = $addresses[0];
        $this->assertEquals(Address::TYPE_INT64, $address->getType());
        $this->assertEquals(0, $address->getAddress());
        $this->assertEquals('realtime', $address->getName());
        $this->assertEquals(4, $address->getSize());

        /** @var ReadRegisterAddress $address */
        $address = $addresses[1];
        $this->assertEquals(Address::TYPE_INT32, $address->getType());
        $this->assertEquals(4, $address->getAddress());
        $this->assertEquals('sys time', $address->getName());
        $this->assertEquals(2, $address->getSize());
    }

    public function requestValuesProvider(): array
    {
        return [
            'first address is bigger in size than second' => [
                [
                    0 => [
                        'addresses' => [
                            ['int64', 1, 'addr1'],
                            ['int32', 2, 'addr2'],
                        ],
                        'startAddress' => 1,
                        'quantity' => 4,
                    ]
                ],
            ],
            'addresses overlap due their size' => [
                [
                    0 => [
                        'addresses' => [
                            ['int64', 0, 'addr1'],
                            ['int64', 2, 'addr2'],
                        ],
                        'startAddress' => 0,
                        'quantity' => 6,
                    ]
                ],
            ],
            'first address is bigger in size than second multiple requests' => [
                [
                    0 => [
                        'addresses' => [
                            ['int64', 1, 'addr1'],
                            ['int32', 2, 'addr2'],
                        ],
                        'startAddress' => 1,
                        'quantity' => 4,
                    ],
                    1 => [
                        'addresses' => [
                            ['int64', 256, 'addr256'],
                            ['int32', 259, 'addr259'],
                        ],
                        'startAddress' => 256,
                        'quantity' => 5,
                    ],
                    2 => [
                        'addresses' => [
                            ['int32', 1259, 'addr1259'],
                        ],
                        'startAddress' => 1259,
                        'quantity' => 2,
                    ]
                ],
            ],
            'first val is at 0 address' => [
                [
                    0 => [
                        'addresses' => [
                            ['int64', 0, 'addr1'],
                            ['int32', 4, 'addr2'],
                        ],
                        'startAddress' => 0,
                        'quantity' => 6,
                    ]
                ],
            ],
            'first val is at 1 address' => [
                [
                    0 => [
                        'addresses' => [
                            ['int64', 1, 'addr1'],
                            ['int32', 5, 'addr2'],
                        ],
                        'startAddress' => 1,
                        'quantity' => 6,
                    ]
                ],
            ],
            'first val is at 0 address vol2' => [
                [
                    0 => [
                        'addresses' => [
                            ['int64', 0, 'realtime'],
                            ['int32', 4, 'sys time'],
                            ['int16', 6, 'day'],
                            ['int16', 7, 'month'],
                        ],
                        'startAddress' => 0,
                        'quantity' => 8,
                    ]
                ],
            ],
        ];
    }

    /**
     * @dataProvider requestValuesProvider
     */
    public function testRequestValues($requestArgs)
    {
        $builder = ReadRegistersBuilder::newReadHoldingRegisters('tcp://127.0.0.1:5022');

        foreach ($requestArgs as $args) {
            if (empty($args['addresses'])) {
                continue;
            }
            foreach ($args['addresses'] as list($method, $address, $name)) {
                $builder = $builder->$method($address, $name);
            }
        }

        $requests = $builder->build();

        $this->assertCount(count($requestArgs), $requests);

        $i = 0;
        foreach ($requestArgs as $args) {
            $request = $requests[$i]->getRequest();


            $this->assertEquals($args['startAddress'], $request->getStartAddress());
            $this->assertEquals($args['quantity'], $request->getQuantity());

            $this->assertCount(count($args['addresses']), $requests[$i]->getAddresses());

            $i++;
        }
    }
}
