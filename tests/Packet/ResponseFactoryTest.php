<?php


namespace Tests\Packet;


use ModbusTcpClient\Packet\ExceptionResponse;
use ModbusTcpClient\Packet\IModbusPacket;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Packet\ResponseFactory;
use PHPUnit\Framework\TestCase;

class ResponseFactoryTest extends TestCase
{
    /**
     * @expectedException \ModbusTcpClient\ModbusException
     * @expectedExceptionMessage Response null or data length too short to be valid packet!
     */
    public function testShouldThrowExceptionOnGarbageData()
    {
        ResponseFactory::parseResponse("\x00\x01\x00\x00\x00\x06\x11\x06");
    }

    /**
     * @expectedException \ModbusTcpClient\ModbusException
     * @expectedExceptionMessage Response null or data length too short to be valid packet!
     */
    public function testShouldThrowExceptionOnNullData()
    {
        ResponseFactory::parseResponse(null);
    }

    public function testShouldParseExceptionResponse()
    {
        //exception for read coils (FC1), error code 3
        $data = "\xda\x87\x00\x00\x00\x03\x00\x81\x03";

        $response = ResponseFactory::parseResponse($data);

        $this->assertInstanceOf(ExceptionResponse::class, $response);
        $this->assertEquals(IModbusPacket::READ_COILS, $response->getFunctionCode());
        $this->assertEquals(3, $response->getErrorCode());
    }

    public function testShouldParseReadHoldingRegistersResponse()
    {
        //trans + proto + len   + uid + fc + qnt + data
        //81 80 + 00 00 + 00 05 + 01  + 03 + 02  + 00 03
        $data = "\x81\x80\x00\x00\x00\x05\x01\x03\x02\x00\x03";

        $response = ResponseFactory::parseResponse($data);

        $this->assertInstanceOf(ReadHoldingRegistersResponse::class, $response);
        $this->assertEquals(IModbusPacket::READ_HOLDING_REGISTERS, $response->getFunctionCode());
        $this->assertEquals([0, 3], $response->getData());

        $header = $response->getHeader();
        $this->assertEquals(1, $header->getUnitId());
        $this->assertEquals(0x8180, $header->getTransactionId());
    }

    public function testShouldParseReadCoilsResponse()
    {
        //trans + proto + len   + uid + fc + qnt + data
        //81 80 + 00 00 + 00 05 + 01  + 01 + 02  + CD 6b
        $data = "\x81\x80\x00\x00\x00\x05\x03\x01\x02\xCD\x6B";

        /* @var ReadCoilsResponse */
        $response = ResponseFactory::parseResponse($data);

        $this->assertInstanceOf(ReadCoilsResponse::class, $response);
        $this->assertEquals(IModbusPacket::READ_COILS, $response->getFunctionCode());
        $this->assertEquals([
            1, 0, 1, 1, 0, 0, 1, 1,  // hex: CD -> bin: 1100 1101 -> reverse for user input: 1011 0011
            1, 1, 0, 1, 0, 1, 1, 0   // hex: 6B -> bin: 0110 1011 -> reverse for user input: 1101 0110
        ], $response->getCoils());

        $header = $response->getHeader();
        $this->assertEquals(3, $header->getUnitId());
        $this->assertEquals(0x8180, $header->getTransactionId());
    }

}