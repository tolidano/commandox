<?php declare(strict_types = 1);

namespace CommandoX\Test\Util;

use CommandoX\Util\Terminal;

require_once dirname(dirname(dirname(__DIR__))) . '/vendor/autoload.php';

// PHPUnit version hack https://stackoverflow.com/questions/6065730/why-fatal-error-class-phpunit-framework-testcase-not-found-in
if (!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase'))
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');

class TerminalTest extends \PHPUnit_Framework_TestCase
{
    public function testGetWidthWhenNoValueThenReturnsDefault() {
        Terminal::beep();
        $actual = Terminal::getWidth();
        $this->assertGreaterThan(20, $actual);
    }

    public function testGetHeightWhenNoValueThenReturnsDefault() {
        Terminal::beep();
        $actual = Terminal::getHeight();
        $this->assertGreaterThan(20, $actual);
    }

    public function testHeaderReturnsPaddedString() {
        $expected = 'X         ';
        $actual = Terminal::header('X', 10);
        $this->assertEquals($expected, $actual);
        $actualNoWidth = Terminal::header('X');
        $this->assertGreaterThan(strlen($expected), strlen($actualNoWidth));
    }

    public function testWrapReturnsWrappedString() {
        $expected = '          X';
        $actual = Terminal::wrap('X', 10);
        $this->assertEquals($expected, $actual);
    }

    public function testPadReturnsPaddedString() {
        $expected = 'X         ';
        $actual = Terminal::pad('X', 10);
        $this->assertEquals($expected, $actual);
    }
}
