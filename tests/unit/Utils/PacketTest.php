<?php

namespace Tests\Utils;

use ModbusTcpClient\Network\IOException;
use ModbusTcpClient\Utils\Packet;
use PHPUnit\Framework\TestCase;

class PacketTest extends TestCase
{

    public function isCompleteLengthProvider(): array
    {
        return [
            'complete error packet is complete' => ["\xda\x87\x00\x00\x00\x03\x00\x81\x03", true],
            'short incomplete error packet is not complete' => ["\xda\x87\x00\x00\x00\x03\x00", false],
            'by len field packet is not complete' => ["\xda\x87\x00\x00\x00\x03\x00\0x81", false],
            'read coils (f1) response packet is complete' => ["\x81\x80\x00\x00\x00\x04\x03\x01\x01\xCD", true],
            'read holding registers (f3) response packet is complete' => ["\x81\x80\x00\x00\x00\x05\x03\x03\x02\xCD\x6B", true],
            'incomplete read holding registers (f3) response packet is not complete' => ["\x81\x80\x00\x00\x00\x05", false],
        ];
    }

    /**
     * @dataProvider isCompleteLengthProvider
     */
    public function testIsCompleteLength($binaryData, $expect)
    {
        $is = Packet::isCompleteLength($binaryData);
        $this->assertEquals($expect, $is);
    }

    public function isCompleteLengthRTUProvider(): array
    {
        return [
            'complete error packet is complete' => ["\x00\x81\x03\x51\x91", true],
            'short incomplete error packet is not complete' => ["\x00\x81\x03\x51", false],
            'read holding registers (f3) response packet is complete' => ["\x01\x03\x04\x00\x00\x00\x00\xfa\x33", true],
            'incomplete read holding registers (f3) response packet is not complete' => ["\x01\x03\x04\x00\x00\x00\x00", false],
        ];
    }

    /**
     * @dataProvider isCompleteLengthRTUProvider
     */
    public function testIsCompleteLengthRTUh($binaryData, $expect)
    {
        $is = Packet::isCompleteLengthRTU($binaryData);
        $this->assertEquals($expect, $is);
    }

    public function testIsCompleteLengthRTUTooLong()
    {
        $this->expectExceptionMessage("packet length more bytes than expected");
        $this->expectException(IOException::class);

        Packet::isCompleteLengthRTU("\x01\x03\x04\x00\x00\x00\x00\xfa\x33\xFF");
    }

    public function isCompleteLengthRTUTypesProvider(): array
    {
        return [ // all have CRC as \x00\x00
            'GetCommEventCounterResponse' => ["\x01\x0b\xFF\xFF\x00\x01\x00\x00", true],
            'MaskWriteRegisterResponse' => ["\x01\x16\x00\x04\x00\xF2\x00\x25\x00\x00", true],
            'ReadCoilsResponse' => ["\x01\x01\x02\xCD\x6B\x00\x00", true],
            'ReadHoldingRegistersResponse' => ["\x01\x03\x02\xCD\x6B\x00\x00", true],
            'ReadInputDiscretesResponse' => ["\x01\x02\x02\xCD\x6B\x00\x00", true],
            'ReadInputRegistersResponse' => ["\x01\x04\x02\xCD\x6B\x00\x00", true],
            'ReadWriteMultipleRegistersResponse' => ["\x01\x17\x02\xCD\x6B\x00\x00", true],
            'WriteMultipleCoilsResponse' => ["\x01\x0F\x04\x10\x00\x03\x00\x00", true],
            'WriteMultipleRegistersResponse' => ["\x01\x10\x04\x10\x00\x03\x00\x00", true],
            'WriteSingleCoilResponse' => ["\x01\x05\x00\x02\xFF\x00\x00\x00", true],
            'WriteSingleRegisterResponse' => ["\x01\x06\x00\x02\xFF\x00\x00\x00", true],
        ];
    }

    /**
     * @dataProvider isCompleteLengthRTUTypesProvider
     */
    public function testIsCompleteLengthRTUTypes($binaryData, $expect)
    {
        $is = Packet::isCompleteLengthRTU($binaryData);
        $this->assertEquals($expect, $is);
    }

    public function testIsCompleteLengthRTUUnknownType()
    {
        $this->expectExceptionMessage("an not determine complete length for unsupported modbus function code");
        $this->expectException(IOException::class);

        Packet::isCompleteLengthRTU("\x01\x00\xFF\xFF\x00\x01\x00\x00");
    }

}
