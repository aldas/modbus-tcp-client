<?php

namespace Tests\Packet\ModbusFunction;

use ModbusTcpClient\Exception\InvalidArgumentException;
use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Exception\ParseException;
use ModbusTcpClient\Packet\ModbusFunction\ReadWriteMultipleRegistersResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Utils\Endian;
use PHPUnit\Framework\TestCase;

class ReadWriteMultipleRegistersResponseTest extends TestCase
{
    protected function setUp(): void
    {
        Endian::$defaultEndian = Endian::LITTLE_ENDIAN; // packets are big endian. setting to default to little should not change output
    }

    protected function tearDown(): void
    {
        Endian::$defaultEndian = Endian::BIG_ENDIAN_LOW_WORD_FIRST;
    }

    public function testPacketToString()
    {
        $this->assertEquals(
            "\x81\x80\x00\x00\x00\x05\x03\x17\x02\xCD\x6B",
            (new ReadWriteMultipleRegistersResponse("\x02\xCD\x6B", 3, 33152))->__toString()
        );
    }

    public function testPacketProperties()
    {
        $packet = new ReadWriteMultipleRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01", 3, 33152);
        $this->assertEquals(ModbusPacket::READ_WRITE_MULTIPLE_REGISTERS, $packet->getFunctionCode());

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
        $packet = new ReadWriteMultipleRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01", 3, 33152);

        $words = $packet->getWords();
        $this->assertCount(3, $words);

        $this->assertEquals("\xCD\x6B", $words[0]->getData());
        $this->assertEquals([0xCD, 0x6B], $words[0]->getBytes());

        $this->assertEquals([0x0, 0x0], $words[1]->getBytes());
        $this->assertEquals([0x0, 0x1], $words[2]->getBytes());
    }

    public function testAsWords()
    {
        $packet = new ReadWriteMultipleRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01", 3, 33152);

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
        $packet = (new ReadWriteMultipleRegistersResponse(
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

    public function testGetWordsFailsWhenByteCountIsNotMod2()
    {
        $this->expectExceptionMessage("getWords needs packet byte count to be multiple of 2");
        $this->expectException(ModbusException::class);

        $packet = new ReadWriteMultipleRegistersResponse("\x07\xCD\x6B\x0\x0\x0\x01\x00", 3, 33152);
        $packet->getWords();
    }

    public function testGetDoubleWords()
    {
        $packet = new ReadWriteMultipleRegistersResponse("\x08\xCD\x6B\x0\x0\x0\x01\x00\x00", 3, 33152);

        $dWords = $packet->getDoubleWords();
        $this->assertCount(2, $dWords);

        $this->assertEquals("\xCD\x6B\x00\x00", $dWords[0]->getData());
        $this->assertEquals([0xCD, 0x6B, 0x0, 0x0], $dWords[0]->getBytes());

        $this->assertEquals([0x0, 0x01, 0x0, 0x0], $dWords[2]->getBytes());
    }

    public function testAsDoubleWords()
    {
        $packet = (new ReadWriteMultipleRegistersResponse(
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
        $packet = (new ReadWriteMultipleRegistersResponse(
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

    public function testOffsetSet()
    {
        $this->expectExceptionMessage("setting value in response is not supported!");
        $this->expectException(ModbusException::class);

        $packet = new ReadWriteMultipleRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01", 3, 33152);
        $packet[50] = 1;
    }

    public function testOffsetUnSet()
    {
        $this->expectExceptionMessage("unsetting value in response is not supported!");
        $this->expectException(ModbusException::class);

        $packet = new ReadWriteMultipleRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01", 3, 33152);
        unset($packet[50]);
    }

    public function testFailWhenByteCountDoesNotMatch()
    {
        $this->expectExceptionMessage("packet byte count does not match bytes in packet! count: 6, actual: 7");
        $this->expectException(ParseException::class);

        new ReadWriteMultipleRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01\x00", 3, 33152);
    }

    public function testOffsetGet()
    {
        $packet = (new ReadWriteMultipleRegistersResponse(
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
        $packet = (new ReadWriteMultipleRegistersResponse(
            "\x06\xCD\x6B\x4\x3\x2\x01",
            3,
            33152
        ))->withStartAddress(50);

        $this->assertEquals([0xCD, 0x6B], $packet->getWordAt(50)->getBytes());
        $this->assertEquals([0x4, 0x3], $packet->getWordAt(51)->getBytes());
        $this->assertEquals([0x2, 0x1], $packet->getWordAt(52)->getBytes());
        $this->assertEquals([0x2, 0x1], $packet->getWordAt(52)->getBytes());
    }

    public function testOffsetGetOutOfBoundsUnder()
    {
        $this->expectExceptionMessage("offset out of bounds");
        $this->expectException(InvalidArgumentException::class);

        $packet = (new ReadWriteMultipleRegistersResponse(
            "\x06\xCD\x6B\x4\x3\x2\x01",
            3,
            33152
        ))->withStartAddress(50);

        $packet[49];
    }

    public function testOffsetGetOutOfBoundsOver()
    {
        $this->expectExceptionMessage("offset out of bounds");
        $this->expectException(InvalidArgumentException::class);

        $packet = (new ReadWriteMultipleRegistersResponse(
            "\x06\xCD\x6B\x04\x03\x02\x01",
            3,
            33152
        ))->withStartAddress(50);

        $packet[53];
    }

    public function testGetDoubleWordsFailsWhenByteCountIsNotMod4()
    {
        $this->expectExceptionMessage("getDoubleWords needs packet byte count to be multiple of 4");
        $this->expectException(ModbusException::class);

        $packet = new ReadWriteMultipleRegistersResponse("\x06\xCD\x6B\x0\x0\x0\x01", 3, 33152);
        $packet->getDoubleWords();
    }

    public function testGetDoubleWordAt()
    {
        $packet = (new ReadWriteMultipleRegistersResponse(
            "\x06\xCD\x6B\x04\x03\x02\x01",
            3,
            33152
        ))->withStartAddress(50);

        $this->assertEquals([0xCD, 0x6B, 0x04, 0x03], $packet->getDoubleWordAt(50)->getBytes());
        $this->assertEquals([0x04, 0x03, 0x02, 0x01], $packet->getDoubleWordAt(51)->getBytes());
    }

    public function testGetDoubleWordAtOutOfBounderUnder()
    {
        $this->expectExceptionMessage("address out of bounds");
        $this->expectException(InvalidArgumentException::class);

        $packet = (new ReadWriteMultipleRegistersResponse(
            "\x06\xCD\x6B\x04\x03\x02\x01",
            3,
            33152
        ))->withStartAddress(50);

        $packet->getDoubleWordAt(49);
    }

    public function testGetDoubleWordAtOutOfBounderOver()
    {
        $this->expectExceptionMessage("address out of bounds");
        $this->expectException(InvalidArgumentException::class);

        $packet = (new ReadWriteMultipleRegistersResponse(
            "\x06\xCD\x6B\x04\x03\x02\x01",
            3,
            33152
        ))->withStartAddress(50);

        $packet->getDoubleWordAt(52);
    }

    public function testGetQuadWordAt()
    {
        $packet = (new ReadWriteMultipleRegistersResponse(
            "\x08\x08\x07\x06\x05\x04\x03\x02\x01",
            3,
            33152
        ))->withStartAddress(50);

        $this->assertEquals(
            [0x08, 0x07, 0x06, 0x05, 0x04, 0x03, 0x02, 0x01],
            $packet->getQuadWordAt(50)->getBytes()
        );
    }

    public function testGetQuadWordAtOutOfBounderUnder()
    {
        $this->expectExceptionMessage("address out of bounds");
        $this->expectException(InvalidArgumentException::class);

        $packet = (new ReadWriteMultipleRegistersResponse(
            "\x08\x08\x07\x06\x05\x04\x03\x02\x01",
            3,
            33152
        ))->withStartAddress(50);

        $this->assertNotNull($packet->getQuadWordAt(50));
        $packet->getQuadWordAt(49);
    }

    public function testGetQuadWordAtOutOfBounderOver()
    {
        $this->expectExceptionMessage("address out of bounds");
        $this->expectException(InvalidArgumentException::class);

        $packet = (new ReadWriteMultipleRegistersResponse(
            "\x08\x08\x07\x06\x05\x04\x03\x02\x01",
            3,
            33152
        ))->withStartAddress(50);

        $this->assertNotNull($packet->getQuadWordAt(50));
        $packet->getQuadWordAt(51);
    }

    public function testGetAsciiString()
    {
        $packet = (new ReadWriteMultipleRegistersResponse("\x08\x01\x00\xF8\x53\x65\x72\x00\x6E", 3, 33152))->withStartAddress(50);
        $this->assertCount(4, $packet->getWords());

        $this->assertEquals('Søren', $packet->getAsciiStringAt(51, 5, Endian::BIG_ENDIAN_LOW_WORD_FIRST));
    }

    public function testGetAsciiStringInvalidAddressLow()
    {
        $this->expectExceptionMessage("startFromWord out of bounds");
        $this->expectException(InvalidArgumentException::class);

        $packet = (new ReadWriteMultipleRegistersResponse("\x08\x01\x00\xF8\x53\x65\x72\x00\x6E", 3, 33152))->withStartAddress(50);
        $this->assertCount(4, $packet->getWords());

        $packet->getAsciiStringAt(49, 5);
    }

    public function testGetAsciiStringInvalidAddressHigh()
    {
        $this->expectExceptionMessage("startFromWord out of bounds");
        $this->expectException(InvalidArgumentException::class);

        $packet = (new ReadWriteMultipleRegistersResponse("\x08\x01\x00\xF8\x53\x65\x72\x00\x6E", 3, 33152))->withStartAddress(50);
        $this->assertCount(4, $packet->getWords());

        $packet->getAsciiStringAt(54, 5);
    }

    public function testGetAsciiStringInvalidLength()
    {
        $this->expectExceptionMessage("length out of bounds");
        $this->expectException(InvalidArgumentException::class);

        $packet = (new ReadWriteMultipleRegistersResponse("\x08\x01\x00\xF8\x53\x65\x72\x00\x6E", 3, 33152))->withStartAddress(50);
        $this->assertCount(4, $packet->getWords());

        $packet->getAsciiStringAt(50, 0);
    }
}
