<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use PHPUnit\Framework\TestCase;

class ReadHoldingRegistersResponseTest extends TestCase
{
    public function testPacketToString()
    {
        $this->assertEquals(
            "\x81\x80\x00\x00\x00\x05\x03\x03\x02\xCD\x6B",
            (new ReadHoldingRegistersResponse("\x02\xCD\x6B", 3, 33152))->__toString()
        );
    }

    public function testPacketProperties()
    {
        $packet = new ReadHoldingRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01", 3, 33152);
        $this->assertEquals(ModbusPacket::READ_HOLDING_REGISTERS, $packet->getFunctionCode());

        $this->assertEquals([0xCD, 0x6B, 0x0, 0x0, 0x0, 0x1], $packet->getData());

        $this->assertCount(3, $packet->getWords());
        $this->assertEquals([0x0, 0x1], $packet->getWords()[2]->getBytes());

        $header = $packet->getHeader();
        $this->assertEquals(33152, $header->getTransactionId());
        $this->assertEquals(0, $header->getProtocolId());
        $this->assertEquals(9, $header->getLength());
        $this->assertEquals(3, $header->getUnitId());
    }

    public function testGetWords()
    {
        $packet = new ReadHoldingRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01", 3, 33152);

        $words = $packet->getWords();
        $this->assertCount(3, $words);

        $this->assertEquals("\xCD\x6B", $words[0]->getData());
        $this->assertEquals([0xCD, 0x6B], $words[0]->getBytes());

        $this->assertEquals([0x0, 0x0], $words[1]->getBytes());
        $this->assertEquals([0x0, 0x1], $words[2]->getBytes());
    }

    public function testAsWords()
    {
        $packet = new ReadHoldingRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01", 3, 33152);

        $words = [];
        foreach ($packet->asWords() as $index => $word) {
            $words[$index] = $word;
        }
        $this->assertCount(3, $words);

        $this->assertEquals("\xCD\x6B", $words[0]->getData());
        $this->assertEquals([0xCD, 0x6B], $words[0]->getBytes());

        $this->assertEquals([0x0, 0x0], $words[1]->getBytes());
        $this->assertEquals([0x0, 0x1], $words[2]->getBytes());
    }

    public function testIterator()
    {
        $packet = (new ReadHoldingRegistersResponse(
            "\x06\xCD\x6B\x0\x0\x0\x01",
            3,
            33152
        ))->withStartAddress(50);

        $wordsAssoc = [];
        foreach ($packet as $address => $word) {
            $wordsAssoc[$address] = $word;
        }

        $words = [];
        foreach ($packet as $word) {
            $words[] = $word;
        }
        $this->assertCount(3, $words);

        $this->assertEquals("\xCD\x6B", $wordsAssoc[50]->getData());
        $this->assertEquals([0xCD, 0x6B], $wordsAssoc[50]->getBytes());

        $this->assertEquals([0x0, 0x0], $wordsAssoc[51]->getBytes());
        $this->assertEquals([0x0, 0x1], $wordsAssoc[52]->getBytes());

        $this->assertEquals([0x0, 0x0], $words[1]->getBytes());
        $this->assertEquals([0x0, 0x1], $words[2]->getBytes());
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ModbusException
     * @expectedExceptionMessage getWords needs packet byte count to be multiple of 2
     */
    public function testGetWordsFailsWhenByteCountIsNotMod2()
    {
        $packet = new ReadHoldingRegistersResponse("\x07\xCD\x6B\x0\x0\x0\x01\x00", 3, 33152);
        $packet->getWords();
    }

    public function testGetDoubleWords()
    {
        $packet = new ReadHoldingRegistersResponse("\x08\xCD\x6B\x0\x0\x0\x01\x00\x00", 3, 33152);

        $dWords = $packet->getDoubleWords();
        $this->assertCount(2, $dWords);

        $this->assertEquals("\xCD\x6B\x00\x00", $dWords[0]->getData());
        $this->assertEquals([0xCD, 0x6B, 0x0, 0x0], $dWords[0]->getBytes());

        $this->assertEquals([0x0, 0x01, 0x0, 0x0], $dWords[2]->getBytes());
    }

    public function testAsDoubleWords()
    {
        $packet = (new ReadHoldingRegistersResponse(
            "\x08\xCD\x6B\x0\x0\x0\x01\x00\x00",
            3,
            33152
        ))->withStartAddress(50);

        $dWordsAssoc = [];
        foreach ($packet->asDoubleWords() as $address => $doubleWord) {
            $dWordsAssoc[$address] = $doubleWord;
        }

        $dWords = [];
        foreach ($packet->asDoubleWords() as $doubleWord) {
            $dWords[] = $doubleWord;
        }
        $this->assertCount(2, $dWordsAssoc);

        $this->assertEquals("\xCD\x6B\x00\x00", $dWordsAssoc[50]->getData());

        $this->assertEquals([0xCD, 0x6B, 0x0, 0x0], $dWordsAssoc[50]->getBytes());
        $this->assertEquals([0x0, 0x01, 0x0, 0x0], $dWordsAssoc[52]->getBytes());

        $this->assertEquals([0xCD, 0x6B, 0x0, 0x0], $dWords[0]->getBytes());
        $this->assertEquals([0x0, 0x01, 0x0, 0x0], $dWords[1]->getBytes());
    }

    public function testOffsetExists()
    {
        $packet = (new ReadHoldingRegistersResponse(
            "\x08\xCD\x6B\x0\x0\x0\x01\x00\x00",
            3,
            33152
        ))->withStartAddress(50);

        $this->assertFalse(isset($packet[49]));
        $this->assertTrue(isset($packet[50]));
        $this->assertTrue(isset($packet[51]));
        $this->assertTrue(isset($packet[52]));
        $this->assertTrue(isset($packet[53]));
        $this->assertFalse(isset($packet[54]));
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ModbusException
     * @expectedExceptionMessage setting value in response is not supported!
     */
    public function testOffsetSet()
    {
        $packet = new ReadHoldingRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01", 3, 33152);
        $packet[50] = 1;
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ModbusException
     * @expectedExceptionMessage unsetting value in response is not supported!
     */
    public function testOffsetUnSet()
    {
        $packet = new ReadHoldingRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01", 3, 33152);
        unset($packet[50]);
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ParseException
     * @expectedExceptionMessage packet byte count does not match bytes in packet! count: 6, actual: 7
     */
    public function testFailWhenByteCountDoesNotMatch()
    {
        new ReadHoldingRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01\x00", 3, 33152);
    }

    public function testOffsetGet()
    {
        $packet = (new ReadHoldingRegistersResponse(
            "\x06\xCD\x6B\x4\x3\x2\x01",
            3,
            33152
        ))->withStartAddress(50);

        $this->assertEquals([0xCD, 0x6B], $packet[50]->getBytes());
        $this->assertEquals([0x4, 0x3], $packet[51]->getBytes());
        $this->assertEquals([0x2, 0x1], $packet[52]->getBytes());
    }

    public function testGetWordAt()
    {
        $packet = (new ReadHoldingRegistersResponse(
            "\x06\xCD\x6B\x4\x3\x2\x01",
            3,
            33152
        ))->withStartAddress(50);

        $this->assertEquals([0xCD, 0x6B], $packet->getWordAt(50)->getBytes());
        $this->assertEquals([0x4, 0x3], $packet->getWordAt(51)->getBytes());
        $this->assertEquals([0x2, 0x1], $packet->getWordAt(52)->getBytes());
        $this->assertEquals([0x2, 0x1], $packet->getWordAt(52)->getBytes());
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage offset out of bounds
     */
    public function testOffsetGetOutOfBoundsUnder()
    {
        $packet = (new ReadHoldingRegistersResponse(
            "\x06\xCD\x6B\x4\x3\x2\x01",
            3,
            33152
        ))->withStartAddress(50);

        $packet[49];
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage offset out of bounds
     */
    public function testOffsetGetOutOfBoundsOver()
    {
        $packet = (new ReadHoldingRegistersResponse(
            "\x06\xCD\x6B\x04\x03\x02\x01",
            3,
            33152
        ))->withStartAddress(50);

        $packet[53];
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ModbusException
     * @expectedExceptionMessage getDoubleWords needs packet byte count to be multiple of 4
     */
    public function testGetDoubleWordsFailsWhenByteCountIsNotMod4()
    {
        $packet = new ReadHoldingRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01", 3, 33152);
        $packet->getDoubleWords();
    }

    public function testGetDoubleWordAt()
    {
        $packet = (new ReadHoldingRegistersResponse(
            "\x06\xCD\x6B\x04\x03\x02\x01",
            3,
            33152
        ))->withStartAddress(50);

        $this->assertEquals([0xCD, 0x6B, 0x04, 0x03], $packet->getDoubleWordAt(50)->getBytes());
        $this->assertEquals([0x04, 0x03, 0x02, 0x01], $packet->getDoubleWordAt(51)->getBytes());
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage address out of bounds
     */
    public function testGetDoubleWordAtOutOfBounderUnder()
    {
        $packet = (new ReadHoldingRegistersResponse(
            "\x06\xCD\x6B\x04\x03\x02\x01",
            3,
            33152
        ))->withStartAddress(50);

        $packet->getDoubleWordAt(49);
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage address out of bounds
     */
    public function testGetDoubleWordAtOutOfBounderOver()
    {
        $packet = (new ReadHoldingRegistersResponse(
            "\x06\xCD\x6B\x04\x03\x02\x01",
            3,
            33152
        ))->withStartAddress(50);

        $packet->getDoubleWordAt(52);
    }

    public function testGetQuadWordAt()
    {
        $packet = (new ReadHoldingRegistersResponse(
            "\x08\x08\x07\x06\x05\x04\x03\x02\x01",
            3,
            33152
        ))->withStartAddress(50);

        $this->assertEquals(
            [0x08, 0x07, 0x06, 0x05, 0x04, 0x03, 0x02, 0x01],
            $packet->getQuadWordAt(50)->getBytes()
        );
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage address out of bounds
     */
    public function testGetQuadWordAtOutOfBounderUnder()
    {

        $packet = (new ReadHoldingRegistersResponse(
            "\x08\x08\x07\x06\x05\x04\x03\x02\x01",
            3,
            33152
        ))->withStartAddress(50);

        $this->assertNotNull($packet->getQuadWordAt(50));
        $packet->getQuadWordAt(49);
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage address out of bounds
     */
    public function testGetQuadWordAtOutOfBounderOver()
    {
        $packet = (new ReadHoldingRegistersResponse(
            "\x08\x08\x07\x06\x05\x04\x03\x02\x01",
            3,
            33152
        ))->withStartAddress(50);

        $this->assertNotNull($packet->getQuadWordAt(50));
        $packet->getQuadWordAt(51);
    }

    public function testGetAsciiString()
    {
        $packet = (new ReadHoldingRegistersResponse("\x08\x01\x00\xF8\x53\x65\x72\x00\x6E", 3, 33152))->withStartAddress(50);
        $this->assertCount(4, $packet->getWords());

        $this->assertEquals('Søren', $packet->getAsciiStringAt(51, 5));
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage startFromWord out of bounds
     */
    public function testGetAsciiStringInvalidAddressLow()
    {
        $packet = (new ReadHoldingRegistersResponse("\x08\x01\x00\xF8\x53\x65\x72\x00\x6E", 3, 33152))->withStartAddress(50);
        $this->assertCount(4, $packet->getWords());

        $packet->getAsciiStringAt(49, 5);
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage startFromWord out of bounds
     */
    public function testGetAsciiStringInvalidAddressHigh()
    {
        $packet = (new ReadHoldingRegistersResponse("\x08\x01\x00\xF8\x53\x65\x72\x00\x6E", 3, 33152))->withStartAddress(50);
        $this->assertCount(4, $packet->getWords());

        $packet->getAsciiStringAt(54, 5);
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\InvalidArgumentException
     * @expectedExceptionMessage length out of bounds
     */
    public function testGetAsciiStringInvalidLength()
    {
        $packet = (new ReadHoldingRegistersResponse("\x08\x01\x00\xF8\x53\x65\x72\x00\x6E", 3, 33152))->withStartAddress(50);
        $this->assertCount(4, $packet->getWords());

        $packet->getAsciiStringAt(50, 0);
    }
}