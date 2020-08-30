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
            'short incomplete error packet is not complete' => ["\xda\x87\x00\x00\x00\x03\x00\x81", false],
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

    public function testIsCompleteLengthTooLong()
    {
        $this->expectExceptionMessage("packet length more bytes than expected");
        $this->expectException(IOException::class);

        Packet::isCompleteLength("\x81\x80\x00\x00\x00\x05\x03\x03\x02\xCD\x6B\xFF");
    }

}
