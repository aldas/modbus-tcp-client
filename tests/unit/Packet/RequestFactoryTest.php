<?php


namespace Tests\Packet;


use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusFunction\GetCommEventCounterRequest;
use ModbusTcpClient\Packet\ModbusFunction\MaskWriteRegisterRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadCoilsRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputDiscretesRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadInputRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadWriteMultipleRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReportServerIDRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleCoilsRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteMultipleRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleCoilRequest;
use ModbusTcpClient\Packet\ModbusFunction\WriteSingleRegisterRequest;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\RequestFactory;
use PHPUnit\Framework\TestCase;

class RequestFactoryTest extends TestCase
{
    public function everyFunctionTypePacket()
    {
        return [
            "ok, parse ReadCoilsRequest" => ["\x00\x01\x00\x00\x00\x06\x10\x01\x00\x6B\x00\x03", ReadCoilsRequest::class],
            "ok, parse ReadInputRegistersRequest" => ["\x00\x01\x00\x00\x00\x06\x11\x04\x00\x6B\x00\x03", ReadInputRegistersRequest::class],
            "ok, parse ReadHoldingRegistersRequest" => ["\x00\x01\x00\x00\x00\x06\x11\x03\x00\x6B\x00\x03", ReadHoldingRegistersRequest::class],
            "ok, parse ReadInputDiscretesRequest" => ["\x00\x01\x00\x00\x00\x06\x11\x02\x00\x6B\x00\x03", ReadInputDiscretesRequest::class],
            "ok, parse ReadWriteMultipleRegistersRequest" => ["\x01\x38\x00\x00\x00\x0f\x11\x17\x04\x10\x00\x01\x01\x12\x00\x02\x04\x00\xc8\x00\x82", ReadWriteMultipleRegistersRequest::class],
            "ok, parse WriteMultipleCoilsRequest" => ["\x01\x38\x00\x00\x00\x08\x11\x0F\x04\x10\x00\x03\x01\x05", WriteMultipleCoilsRequest::class],
            "ok, parse WriteMultipleRegistersRequest" => ["\x01\x38\x00\x00\x00\x0d\x11\x10\x04\x10\x00\x03\x06\x00\xC8\x00\x82\x87\x01", WriteMultipleRegistersRequest::class],
            "ok, parse WriteSingleCoilRequest" => ["\x00\x01\x00\x00\x00\x06\x11\x05\x00\x6B\xFF\x00", WriteSingleCoilRequest::class],
            "ok, parse WriteSingleRegisterRequest" => ["\x00\x01\x00\x00\x00\x06\x11\x06\x00\x6B\x01\x01", WriteSingleRegisterRequest::class],
            "ok, parse GetCommEventCounterRequest" => ["\x01\x38\x00\x00\x00\x02\x11\x0b", GetCommEventCounterRequest::class],
            "ok, parse MaskWriteRegisterRequest" => ["\x01\x38\x00\x00\x00\x08\x11\x16\x04\x10\x00\x01\x00\x02", MaskWriteRegisterRequest::class],
            "ok, parse ReportServerIDRequest" => ["\x01\x38\x00\x00\x00\x02\x11\x11", ReportServerIDRequest::class],
        ];
    }

    /**
     * @dataProvider everyFunctionTypePacket
     */
    public function testShouldParseRequestPacket($packet, $expectedClass)
    {
        $request = RequestFactory::parseRequest($packet);
        self::assertInstanceOf($expectedClass, $request);
    }

    public function testShouldThrowExceptionOnGarbageData()
    {
        $this->expectExceptionMessage("Request null or data length too short to be valid packet!");
        $this->expectException(ModbusException::class);

        RequestFactory::parseRequest("\x00\x01\x00\x00\x00\x06\x11");
    }

    public function testShouldThrowExceptionOnNullData()
    {
        $this->expectExceptionMessage("Request null or data length too short to be valid packet!");
        $this->expectException(ModbusException::class);

        RequestFactory::parseRequest(null);
    }

    public function testShouldParseReadHoldingRegistersResponse()
    {
        //trans + proto + len   + uid + fc + start addr + qnt
        $data = "\x00\x01\x00\x00\x00\x06\x11\x03\x00\x6B\x00\x03";

        /** @var ReadHoldingRegistersRequest $response */
        $response = RequestFactory::parseRequest($data);

        self::assertInstanceOf(ReadHoldingRegistersRequest::class, $response);
        self::assertEquals(ModbusPacket::READ_HOLDING_REGISTERS, $response->getFunctionCode());

        self::assertEquals(3, $response->getQuantity());
        self::assertEquals(107, $response->getStartAddress());

        $header = $response->getHeader();
        self::assertEquals(17, $header->getUnitId());
        self::assertEquals(0x0001, $header->getTransactionId());
    }

    public function testInvalidFunctionCodeParse()
    {
        //trans + proto + len   + uid + fc + addr + number of coils
        //81 80 + 00 00 + 00 05 + 03  + 20 + 00 01 + 00 0A
        $data = "\x81\x80\x00\x00\x00\x06\x03\x20\x00\x01\x00\x0A";

        /** @var ErrorResponse $response */
        $response = RequestFactory::parseRequest($data);
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertEquals(1, $response->getErrorCode());
    }

    public function testInvalidFunctionCodeParse2()
    {
        //trans + proto + len   + uid + fc + addr + number of coils
        //81 80 + 00 00 + 00 05 + 03  + 83 + 00 01 + 00 0A
        $data = "\x81\x80\x00\x00\x00\x06\x03\x83\x00\x01\x00\x0A";

        /** @var ErrorResponse $response */
        $response = RequestFactory::parseRequest($data);
        self::assertInstanceOf(ErrorResponse::class, $response);
        self::assertEquals(1, $response->getErrorCode());
    }

    public function testInvalidFunctionCodeParseWithThrow()
    {
        $this->expectExceptionMessage("Illegal function");
        $this->expectException(ModbusException::class);

        //trans + proto + len   + uid + fc + addr + number of coils
        //81 80 + 00 00 + 00 05 + 03  + 20 + 00 01 + 00 0A
        $data = "\x81\x80\x00\x00\x00\x06\x03\x20\x00\x01\x00\x0A";

        RequestFactory::parseRequestOrThrow($data);
    }

    public function testShouldParseOrThrowSuccess()
    {
        //trans + proto + len   + uid + fc + start addr + qnt
        $data = "\x00\x01\x00\x00\x00\x06\x11\x03\x00\x6B\x00\x03";

        /** @var ReadHoldingRegistersRequest $response */
        $response = RequestFactory::parseRequestOrThrow($data);

        self::assertInstanceOf(ReadHoldingRegistersRequest::class, $response);
    }

}
