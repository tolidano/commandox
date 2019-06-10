<?php

namespace CommandoX\Test\Util;

use CommandoX\Util\Terminal;

require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';
require_once 'TestEnum.php';

// PHPUnit version hack https://stackoverflow.com/questions/6065730/why-fatal-error-class-phpunit-framework-testcase-not-found-in
if (!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
}

class EnumTest extends \PHPUnit_Framework_TestCase
{
    public function testEnumWhenInvalidThenThrows()
    {
        $this->expectException(\Exception::class);
        new TestEnum(2);
    }

    public function testEnumWhenInvalidGetThenThrows()
    {
        $this->expectException(\Exception::class);
        $test = new TestEnum(1);
        $test->notValue;
    }
}
