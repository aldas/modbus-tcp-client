<?php


namespace Tests\Packet;


use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusApplicationHeader;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputDiscretesResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\ReadWriteMultipleRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleCoilsResponse;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleRegistersResponse;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleCoilResponse;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleRegisterResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\ResponseFactory;
use PHPUnit\Framework\TestCase;

class ResponseFactoryTest extends TestCase
{
    /**
     * @expectedException \ModbusTcpClient\Exception\ModbusException
     * @expectedExceptionMessage Response null or data length too short to be valid packet!
     */
    public function testShouldThrowExceptionOnGarbageData()
    {
        ResponseFactory::parseResponse("\x00\x01\x00\x00\x00\x06\x11\x06");
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ModbusException
     * @expectedExceptionMessage Response null or data length too short to be valid packet!
     */
    public function testShouldThrowExceptionOnNullData()
    {
        ResponseFactory::parseResponse(null);
    }

    public function testShouldParseErrorResponse()
    {
        //exception for read coils (FC1), error code 3
        $data = "\xda\x87\x00\x00\x00\x03\x00\x81\x03";

        /* @var $response ErrorResponse */
        $response = ResponseFactory::parseResponse($data);

        $this->assertInstanceOf(ErrorResponse::class, $response);
        $this->assertEquals(ModbusPacket::READ_COILS, $response->getFunctionCode());
        $this->assertEquals(3, $response->getErrorCode());
        $this->assertEquals('Illegal data value', $response->getErrorMessage());
    }

    public function testShouldParseErrorResponse2()
    {
        $data = (new ErrorResponse(new ModbusApplicationHeader(2, 0, 55943), 1, 3))->__toString();

        /* @var $response ErrorResponse */
        $response = ResponseFactory::parseResponse($data);

        $this->assertInstanceOf(ErrorResponse::class, $response);
        $this->assertEquals(ModbusPacket::READ_COILS, $response->getFunctionCode());
        $this->assertEquals(3, $response->getErrorCode());
        $this->assertEquals('Illegal data value', $response->getErrorMessage());
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ModbusException
     * @expectedExceptionMessage Illegal data value
     */
    public function testShouldThrowExceptionOnErrorResponse()
    {
        //exception for read coils (FC1), error code 3
        $data = "\xda\x87\x00\x00\x00\x03\x00\x81\x03";

        ResponseFactory::parseResponseOrThrow($data);
    }

    public function testShouldParseReadHoldingRegistersResponse()
    {
        //trans + proto + len   + uid + fc + qnt + data
        //81 80 + 00 00 + 00 05 + 01  + 03 + 02  + 00 03
        $data = "\x81\x80\x00\x00\x00\x05\x01\x03\x02\x00\x03";

        $response = ResponseFactory::parseResponse($data);

        $this->assertInstanceOf(ReadHoldingRegistersResponse::class, $response);
        $this->assertEquals(ModbusPacket::READ_HOLDING_REGISTERS, $response->getFunctionCode());
        $this->assertEquals([0, 3], $response->getData());

        $header = $response->getHeader();
        $this->assertEquals(1, $header->getUnitId());
        $this->assertEquals(0x8180, $header->getTransactionId());
    }

    public function testShouldParseReadInputRegistersResponse()
    {
        //trans + proto + len   + uid + fc + qnt + data
        //81 80 + 00 00 + 00 05 + 01  + 04 + 02  + 00 03
        $data = "\x81\x80\x00\x00\x00\x05\x01\x04\x02\x00\x03";

        $response = ResponseFactory::parseResponse($data);

        $this->assertInstanceOf(ReadInputRegistersResponse::class, $response);
        $this->assertEquals(ModbusPacket::READ_INPUT_REGISTERS, $response->getFunctionCode());
        $this->assertEquals([0, 3], $response->getData());

        $header = $response->getHeader();
        $this->assertEquals(1, $header->getUnitId());
        $this->assertEquals(0x8180, $header->getTransactionId());
    }

    public function testShouldParseReadCoilsResponse()
    {
        //trans + proto + len   + uid + fc + qnt + data
        //81 80 + 00 00 + 00 05 + 03  + 01 + 02  + CD 6b
        $data = "\x81\x80\x00\x00\x00\x05\x03\x01\x02\xCD\x6B";

        /* @var ReadCoilsResponse */
        $response = ResponseFactory::parseResponse($data);

        $this->assertInstanceOf(ReadCoilsResponse::class, $response);
        $this->assertEquals(ModbusPacket::READ_COILS, $response->getFunctionCode());
        $this->assertEquals([
            1, 0, 1, 1, 0, 0, 1, 1,  // hex: CD -> bin: 1100 1101 -> reverse for user input: 1011 0011
            1, 1, 0, 1, 0, 1, 1, 0   // hex: 6B -> bin: 0110 1011 -> reverse for user input: 1101 0110
        ], $response->getCoils());

        $header = $response->getHeader();
        $this->assertEquals(3, $header->getUnitId());
        $this->assertEquals(0x8180, $header->getTransactionId());
    }

    public function testShouldParseReadInputDiscretesResponse()
    {
        //trans + proto + len   + uid + fc + qnt + data
        //81 80 + 00 00 + 00 05 + 03  + 02 + 02  + CD 6b
        $data = "\x81\x80\x00\x00\x00\x05\x03\x02\x02\xCD\x6B";

        /* @var ReadInputDiscretesResponse $response */
        $response = ResponseFactory::parseResponse($data);

        $this->assertInstanceOf(ReadInputDiscretesResponse::class, $response);
        $this->assertEquals(ModbusPacket::READ_INPUT_DISCRETES, $response->getFunctionCode());
        $this->assertEquals([
            1, 0, 1, 1, 0, 0, 1, 1,  // hex: CD -> bin: 1100 1101 -> reverse for user input: 1011 0011
            1, 1, 0, 1, 0, 1, 1, 0   // hex: 6B -> bin: 0110 1011 -> reverse for user input: 1101 0110
        ], $response->getCoils());

        $header = $response->getHeader();
        $this->assertEquals(3, $header->getUnitId());
        $this->assertEquals(0x8180, $header->getTransactionId());
    }

    public function testShouldParseWriteSingleCoilResponse()
    {
        //trans + proto + len   + uid + fc + addr + data
        //81 80 + 00 00 + 00 05 + 03  + 05 + 00 02  + FF 00
        $data = "\x81\x80\x00\x00\x00\x06\x03\x05\x00\x02\xFF\x00";

        /* @var WriteSingleCoilResponse $response */
        $response = ResponseFactory::parseResponse($data);

        $this->assertInstanceOf(WriteSingleCoilResponse::class, $response);
        $this->assertEquals(ModbusPacket::WRITE_SINGLE_COIL, $response->getFunctionCode());
        $this->assertTrue($response->isCoil());

        $header = $response->getHeader();
        $this->assertEquals(3, $header->getUnitId());
        $this->assertEquals(0x8180, $header->getTransactionId());
    }

    public function testShouldParseWriteSingleRegisterResponse()
    {
        //trans + proto + len   + uid + fc + addr + data
        //81 80 + 00 00 + 00 05 + 03  + 06 + 00 02  + FF FF
        $data = "\x81\x80\x00\x00\x00\x06\x03\x06\x00\x02\xFF\xFF";

        /* @var WriteSingleRegisterResponse $response */
        $response = ResponseFactory::parseResponse($data);

        $this->assertInstanceOf(WriteSingleRegisterResponse::class, $response);
        $this->assertEquals(ModbusPacket::WRITE_SINGLE_REGISTER, $response->getFunctionCode());
        $this->assertEquals(65535, $response->getWord()->getUInt16());

        $header = $response->getHeader();
        $this->assertEquals(3, $header->getUnitId());
        $this->assertEquals(0x8180, $header->getTransactionId());
    }

    public function testShouldParseWriteMultipleCoilsResponse()
    {
        //trans + proto + len   + uid + fc + addr + number of coils
        //81 80 + 00 00 + 00 05 + 03  + 0F + 00 01 + 00 0A
        $data = "\x81\x80\x00\x00\x00\x06\x03\x0F\x00\x01\x00\x0A";

        /* @var WriteMultipleCoilsResponse $response */
        $response = ResponseFactory::parseResponse($data);

        $this->assertInstanceOf(WriteMultipleCoilsResponse::class, $response);
        $this->assertEquals(ModbusPacket::WRITE_MULTIPLE_COILS, $response->getFunctionCode());
        $this->assertEquals(10, $response->getCoilCount());

        $header = $response->getHeader();
        $this->assertEquals(3, $header->getUnitId());
        $this->assertEquals(0x8180, $header->getTransactionId());
    }

    public function testShouldParseWriteMultipleRegistersResponse()
    {
        //trans + proto + len   + uid + fc + addr + number of coils
        //81 80 + 00 00 + 00 05 + 03  + 10 + 00 01 + 00 0A
        $data = "\x81\x80\x00\x00\x00\x06\x03\x10\x00\x01\x00\x0A";

        /* @var WriteMultipleRegistersResponse $response */
        $response = ResponseFactory::parseResponse($data);

        $this->assertInstanceOf(WriteMultipleRegistersResponse::class, $response);
        $this->assertEquals(ModbusPacket::WRITE_MULTIPLE_REGISTERS, $response->getFunctionCode());
        $this->assertEquals(10, $response->getRegistersCount());

        $header = $response->getHeader();
        $this->assertEquals(3, $header->getUnitId());
        $this->assertEquals(0x8180, $header->getTransactionId());
    }

    public function testShouldParseReadWriteMultipleRegistersResponse()
    {
        //trans + proto + len   + uid + fc + qnt + data
        //81 80 + 00 00 + 00 05 + 01  + 17 + 02  + 00 03
        $data = "\x81\x80\x00\x00\x00\x05\x01\x17\x02\x00\x03";

        $response = ResponseFactory::parseResponse($data);

        $this->assertInstanceOf(ReadWriteMultipleRegistersResponse::class, $response);
        $this->assertEquals(ModbusPacket::READ_WRITE_MULTIPLE_REGISTERS, $response->getFunctionCode());
        $this->assertEquals([0, 3], $response->getData());

        $header = $response->getHeader();
        $this->assertEquals(1, $header->getUnitId());
        $this->assertEquals(0x8180, $header->getTransactionId());
    }

    /**
     * @expectedException \ModbusTcpClient\Exception\ParseException
     * @expectedExceptionMessage Unknown function code '17' read from response packet
     */
    public function testInvalidFunctionCodeParse()
    {
        //trans + proto + len   + uid + fc + addr + number of coils
        //81 80 + 00 00 + 00 05 + 03  + 11 + 00 01 + 00 0A
        $data = "\x81\x80\x00\x00\x00\x06\x03\x11\x00\x01\x00\x0A";

        ResponseFactory::parseResponse($data);
    }

}
