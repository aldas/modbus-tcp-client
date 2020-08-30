<?php

namespace Tests\unit\Network;


use ModbusTcpClient\Exception\ModbusException;
use ModbusTcpClient\Network\ResultContainer;
use PHPUnit\Framework\TestCase;

class ResultContainerTest extends TestCase
{
    public function testGetData()
    {
        $c = new ResultContainer(['temperature' => 100], []);

        $this->assertEquals(['temperature' => 100], $c->getData());
        $this->assertFalse($c->hasErrors());
    }

    public function testHasErrors()
    {
        $c = new ResultContainer([], ['error']);

        $this->assertTrue($c->hasErrors());
        $this->assertEquals(['error'], $c->getErrors());
    }

    public function testHasNotErrors()
    {
        $c = new ResultContainer([], []);

        $this->assertFalse($c->hasErrors());
    }

    public function testArrayAccessSettingIsNotSupported()
    {
        $this->expectExceptionMessage("setting value is not supported!");
        $this->expectException(ModbusException::class);

        $c = new ResultContainer([], []);
        $c[0] = 1;
    }

    public function testArrayAccessUnsettingIsNotSupported()
    {
        $this->expectExceptionMessage("setting value is not supported!");
        $this->expectException(ModbusException::class);

        $c = new ResultContainer([], []);
        unset($c[0]);
    }

    public function testOffsetExists()
    {
        $c = new ResultContainer(['temperature' => 100], []);

        $this->assertTrue(isset($c['temperature']));
        $this->assertFalse(isset($c['not_exists']));
    }

    public function testIterator()
    {
        $c = new ResultContainer(['temperature' => 100], []);

        foreach ($c as $key => $value) {
            $this->assertEquals('temperature', $key);
            $this->assertEquals(100, $value);
        }
    }

    public function testIterator2()
    {
        $c = new ResultContainer([['temperature' => 100]], []);

        foreach ($c as $key => $value) {
            $this->assertEquals(0, $key);
            $this->assertEquals(['temperature' => 100], $value);
        }
    }

}
