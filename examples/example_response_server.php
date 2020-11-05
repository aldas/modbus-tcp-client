<?php

if (php_sapi_name() !== 'cli') {
    echo 'Should be used only in command line interface';
    return;
}

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/logger.php';

use ModbusTcpClient\Packet\ErrorResponse;
use ModbusTcpClient\Packet\ModbusApplicationHeader;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersRequest;
use ModbusTcpClient\Packet\ModbusFunction\ReadHoldingRegistersResponse;
use ModbusTcpClient\Packet\ModbusPacket;
use ModbusTcpClient\Packet\RequestFactory;
use ModbusTcpClient\Utils\Packet;
use ModbusTcpClient\Utils\Types;
use React\EventLoop\Factory;
use React\Socket\Server;

$address = getenv('MODBUS_SERVER_BIND_ADDRESS') ?: '127.0.0.1';
$port = getenv('MODBUS_SERVER_PORT') ?: '5020';

$logger = new EchoLogger();

$loop = Factory::create();
$socket = new Server("${address}:{$port}", $loop);

$socket->on('connection', function (React\Socket\ConnectionInterface $conn) use ($socket, $loop, $logger) {
    $logger->debug($conn->getRemoteAddress() . ": connected: ");

    // buffer for received bytes for that connection
    $receivedData = b'';
    $conn->on('data', function ($data) use ($conn, $logger, &$receivedData) {
        $logger->debug($conn->getRemoteAddress() . ": received: " . unpack('H*', $data)[1]);

        // there are rare cases when MODBUS packet is received by multiple fragmented TCP packets and it could
        // take PHP multiple reads from stream to get full packet. So we concatenate data and check if all that
        // we have received makes a complete modbus packet.
        // NB: `Packet::isCompleteLength` is suitable only for modbus TCP packets
        $receivedData .= $data;
        if (Packet::isCompleteLength($receivedData)) {
            $logger->debug($conn->getRemoteAddress() . ": complete packet: " . unpack('H*', $receivedData)[1]);

            $request = null;
            try {
                $request = RequestFactory::parseRequest($receivedData);
            } catch (Exception $exception) {
                // something went totally wrong. we should end up here.
                $response = new ErrorResponse(new ModbusApplicationHeader(2, 0, 0),
                    ModbusPacket::READ_HOLDING_REGISTERS,
                    4 // Server failure
                );
                $conn->write($response);
                $conn->end();
            } finally {
                $receivedData = b'';
            }

            if ($request instanceof ErrorResponse) {
                // could not parse request. something wrong with packet
                $conn->write($request);
                $conn->end();
            }
            if (!$request instanceof ReadHoldingRegistersRequest) {
                // FIXME: send error for not implemented modbus functions
                $response = new ErrorResponse(new ModbusApplicationHeader(2, 0, 0),
                    ModbusPacket::READ_HOLDING_REGISTERS,
                    4 // Server failure
                );
                $conn->write($response);
                $conn->end();
            }

            // compose response out of request. respond with zeroed registers
            $header = $request->getHeader();
            $quantity = $request->getQuantity();
            $data = Types::toByte($quantity * 2) . str_repeat("\x00\x00", $quantity);
            $response = new ReadHoldingRegistersResponse(
                $data,
                $header->getUnitId(),
                $header->getTransactionId()
            );
            $logger->debug($conn->getRemoteAddress() . ": response packet: " . unpack('H*', $response)[1]);

            $conn->write($response);
            $conn->end();
        }
    });

    $conn->on('close', function () use ($conn, $logger) {
        $logger->debug($conn->getRemoteAddress() . ": disconnect");
    });
});

$loop->run();
