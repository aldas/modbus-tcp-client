<?php

namespace Tests\unit\Composer\Write\Coil;


use ModbusTcpClient\Composer\AddressSplitter;
use ModbusTcpClient\Composer\Write\Coil\WriteCoilAddress;
use ModbusTcpClient\Composer\Write\Coil\WriteCoilAddressSplitter;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleCoilsRequest;
use PHPUnit\Framework\TestCase;

class WriteCoilAddressSplitterTest extends TestCase
{
    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage Trying to write addresses that seem share their memory range! 256 with 256
     */
    public function testSplitSameAddress()
    {
        $splitter = new WriteCoilAddressSplitter(WriteMultipleCoilsRequest::class);

        $requests = $splitter->split([
            'tcp://127.0.0.1' . AddressSplitter::UNIT_ID_PREFIX . '1' => [
                new WriteCoilAddress(256, true),
                new WriteCoilAddress(256, false),
            ]
        ]);

        $this->assertEquals(1, $requests);
    }
}
