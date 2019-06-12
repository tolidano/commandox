<?php

namespace CommandoX\Test;

use CommandoX\Command;

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';
require_once 'SubclassCommand.php';

// PHPUnit version hack https://stackoverflow.com/questions/6065730/why-fatal-error-class-phpunit-framework-testcase-not-found-in
if (!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
}

class CommandTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Ensure subclasses of Command work properly with define()
     *
     * @test
     */
    public function testWhenCommandExtendedThenSubclassReturned()
    {
        $cmd = SubclassCommand::define(['filename']);
        $this->assertInstanceOf('CommandoX\Test\SubclassCommand', $cmd);
    }

    /**
     * Test anonymous arguments
     *
     * @test
     */
    public function testCommandoXAnon()
    {
        $tokens = ['filename', 'arg1', 'arg2', 'arg3'];
        $cmd = new Command($tokens);
        $this->assertEquals($tokens[1], $cmd[0]);
    }

    /**
     * Test named flags
     *
     * @test
     */
    public function testCommandoXFlag()
    {
        // Single flag
        $tokens = ['filename', '-f', 'val'];
        $cmd = new Command($tokens);
        $cmd->option('f');
        $this->assertEquals($tokens[2], $cmd['f']);

        // Single alias
        $tokens = ['filename', '--foo', 'val'];
        $cmd = new Command($tokens);
        $cmd->option('f')->alias('foo');
        $this->assertEquals($tokens[2], $cmd['f']);
        $this->assertEquals($tokens[2], $cmd['foo']);

        // Multiple flags
        $tokens = ['filename', '-f', 'val', '-g', 'val2'];
        $cmd = new Command($tokens);
        $cmd->option('f')->option('g');
        $this->assertEquals($tokens[2], $cmd['f']);
        $this->assertEquals($tokens[4], $cmd['g']);

        // Single flag with anonymous argument
        $tokens = ['filename', '-f', 'val', 'arg1'];
        $cmd = new Command($tokens);
        $cmd->option('f')->option();
        $this->assertEquals($tokens[3], $cmd[0]);

        // Single flag with anonymous argument
        $tokens = ['filename', '-f', 'val', 'arg1'];
        $cmd = new Command($tokens);
        $cmd->option('f');
        $this->assertEquals($tokens[3], $cmd[0]);
        $opts = $cmd->getOptions();
        $this->assertEquals(4, count($opts)); // --help, -h, -f, and arg

        // Define flag with `flag` and a named argument
        $tokens = ['filename', '-f', 'val', 'arg1'];
        $cmd = new Command($tokens);
        $cmd->flag('f')->argument();
        $this->assertEquals($tokens[3], $cmd[0]);
        $this->assertEquals($tokens[2], $cmd['f']);
        $flags = $cmd->getFlags();
        $args = $cmd->getArguments();
        $this->assertEquals(3, count($flags)); // --help, -h, and -f
        $this->assertEquals(1, count($args)); // just one
    }

    public function testImplicitAndExplicitParse()
    {
        // Implicit
        $tokens = ['filename', 'arg1', 'arg2', 'arg3'];
        $cmd = new Command($tokens);
        $this->assertFalse($cmd->isParsed());
        $cmd[0];
        $this->assertTrue($cmd->isParsed());

        // Explicit
        $cmd = new Command($tokens);
        $this->assertFalse($cmd->isParsed());
        $cmd->parse();
        $this->assertTrue($cmd->isParsed());
    }

    public function testRetrievingOptionNamed()
    {
        // Short flag
        $tokens = ['filename', '-f', 'val'];
        $cmd = new Command($tokens);

        $cmd->option('f')->require();
        $this->assertTrue($cmd->getOption('f')->isRequired());
        $cmd->option('f')->require(false);
        $this->assertFalse($cmd->getOption('f')->isRequired());

        // Make sure there is still only one option
        $this->assertEquals(1, $cmd->getSize());
    }

    /**
     * Must have required arguments
     *
     * @expectedException \Exception
     * @test
     */
    public function testWhenRequiredArgumentNotPassedThenThrows()
    {
        $tokens = ['filename'];
        $cmd = new Command($tokens);
        $cmd->doNotTrapErrors();

        $cmd->option('f')->require();
        $cmd->parse();
    }

    public function testRetrievingOptionAnonymous()
    {
        // Anonymous
        $tokens = ['filename', 'arg1', 'arg2', 'arg3'];
        $cmd = new Command($tokens);

        $cmd->option()->require();
        $this->assertTrue($cmd->getOption(0)->isRequired());
        $cmd->option(0)->require(false);
        $this->assertFalse($cmd->getOption(0)->isRequired());

        $this->assertEquals(1, $cmd->getSize());
    }

    public function testBooleanOption()
    {
        // with boolean flag
        $tokens = ['filename', 'arg1', '-b', 'arg2'];
        $cmd = new Command($tokens);
        $cmd->option('b')->boolean();
        $this->assertTrue($cmd['b']);

        // without boolean flag
        $tokens = ['filename', 'arg1', 'arg2'];
        $cmd = new Command($tokens);
        $cmd->option('b')->boolean();
        $this->assertFalse($cmd['b']);

        // try inverse boolean default operations
        // with bool flag
        $tokens = ['filename', 'arg1', '-b', 'arg2'];
        $cmd = new Command($tokens);
        $cmd->option('b')
            ->default(true)
            ->boolean();
        $this->assertFalse($cmd['b']);
        // without
        $tokens = ['filename', 'arg1', 'arg2'];
        $cmd = new Command($tokens);
        $cmd->option('b')
            ->default(true)
            ->boolean();
        $this->assertTrue($cmd['b']);
    }

    public function testIncrementOption()
    {
        $tokens = ['filename', '-vvvv'];
        $cmd = new Command($tokens);
        $cmd->flag('v')
            ->aka('verbosity')
            ->increment();

        $this->assertEquals(4, $cmd['verbosity']);
    }

    public function testIncrementOptionMaxValue()
    {
        $tokens = ['filename', '-vvvv'];
        $cmd = new Command($tokens);
        $cmd->flag('v')
            ->aka('verbosity')
            ->increment(3);

        $this->assertEquals(3, $cmd['verbosity']);
    }

    public function testGetValues()
    {
        $tokens = ['filename', '-a', 'v1', '-b', 'v2', 'v3', 'v4', 'v5'];
        $cmd = new Command($tokens);
        $cmd->flag('a')->flag('b')->aka('boo')->useDefaultHelp(false);

        $this->assertEquals(['v3', 'v4', 'v5'], $cmd->getArgumentValues());
        $this->assertEquals(['a' => 'v1', 'b' => 'v2'], $cmd->getFlagValues());

        $tokens = ['filename'];
        $cmd = new Command($tokens);
        $cmd->argument();
        $this->assertEmpty($cmd->getArgumentValues());
    }

    /**
     * Ensure that requirements are resolved correctly
     */
    public function testRequirementsOnOptionsValid()
    {
        $tokens = ['filename', '-a', 'v1', '-b', 'v2'];
        $cmd = new Command($tokens);

        $cmd->option('b');
        $cmd->option('a')->needs('b');

        $this->assertEquals($cmd['a'], 'v1');
    }

    /**
     * Test that an exception is thrown when an option isn't set
     *
     * @expectedException \InvalidArgumentException
     * @test
     */
    public function testRequirementsOnOptionsMissing()
    {
        $tokens = ['filename', '-a', 'v1'];
        $cmd = new Command($tokens);

        $cmd->trapErrors(false)
            ->beepOnError(false);
        $cmd->option('b');
        $cmd->option('a')->needs('b');
    }

    /**
     * Test various properties of options
     *
     * @test
     */
    public function testWhenDefinedThenPropertiesPersist()
    {
        $tokens = ['filename', 'test'];
        $cmd = new Command($tokens);
        $cmd->option()->referToAs('abc')->must(function ($value) {
            $valid = ['test'];
            return in_array($value, $valid);
        })->map(function ($value) {
            $map = ['test' => 'tset'];
            if (array_key_exists($value, $map)) {
                $value = $map[$value];
            }
            return "-$value-";
        });
        $value = $cmd[0];
        $this->assertEquals('-tset-', $value);
    }

    /**
     * Test help
     *
     * @test
     */
    public function testGetHelp()
    {
        $tokens = ['filename'];
        $cmd = new Command($tokens);
        $extraHelp = 'interesting';
        $cmd->setHelp($extraHelp);
        $help = $cmd->getHelp();
        $testHelp = '--help';
        $this->assertGreaterThan(0, strpos($help, $testHelp));
        $this->assertGreaterThan(0, strpos($help, $extraHelp));
    }

    /**
     * Test default help on
     *
     * @test
     */
    public function testWhenDefaultHelpTrueThenHelpReturns()
    {
        $tokens = ['filename', '--help'];
        $cmd = new Command($tokens);
        $cmd->parse();
        $this->assertTrue($cmd->didShowHelp());

        $tokens = ['filename', '-h'];
        $cmd = new Command($tokens);
        $cmd->parse();
        $this->assertTrue($cmd->didShowHelp());
    }

    /**
     * Test array unset succeeds
     */
    public function testWhenUnsetOnArrayThenRemoved()
    {
        $tokens = ['filename', 'test', 'test2'];
        $cmd = new Command($tokens);
        $this->assertEquals('test', $cmd[0]);
        unset($cmd[0]);
        $this->assertNull($cmd[0]);
        if (!isset($cmd[1])) {
            $val = $cmd[1];
            $this->assertEquals(null, $val);
        }
    }

    /**
     * Test for unknown methods
     *
     * @expectedException \Exception
     * @test
     */
    public function testCommandWhenMethodNotDefinedThenThrows()
    {
        $tokens = ['filename'];
        $cmd = new Command($tokens);
        $cmd->nonexistentMethod();
    }

    /**
     * Test array interface/iterator usage succeeds
     */
    public function testWhenIteratorUsageThenSucceeds()
    {
        $tokens = ['filename', 'test', 'test2'];
        $cmd = new Command($tokens);
        $cmd[0];
        $cmd->rewind();
        $cmd->next(); //skip -h
        $cmd->next(); //skip --help
        $val = $cmd->current();
        $key = $cmd->key();
        $this->assertEquals('test', $val);
        $this->assertEquals(2, $key);
    }

    /**
     * Test __toString
     *
     * @test
     */
    public function testToStringIncludesHelp()
    {
        $scriptName = 'filename';
        $tokens = [$scriptName, 'test'];
        $cmd = new Command($tokens);
        $help = 'TO STRING ' . $cmd;
        $this->assertGreaterThan(0, strpos($help, $scriptName));
    }

    /**
     * Test array interface throws on set
     *
     * @expectedException \Exception
     * @test
     */
    public function testWhenSetOnArrayThenThrows()
    {
        $tokens = ['filename'];
        $cmd = new Command($tokens);
        $cmd[0] = 'test';
    }

    /**
     * Test malformed arguments while errors are not trapped
     *
     * @expectedException \Exception
     * @test
     */
    public function testWhenMalformedArgumentAndTrapFalseThenThrows()
    {
        $tokens = ['filename', '-*test'];
        $cmd = new Command($tokens);
        $cmd->doNotTrapErrors();
        $cmd->parse();
    }

    /**
     * Test default help off
     */
    public function testWhenDefaultHelpFalseThenHelpErrors()
    {
        $tokens = ['filename', '--help'];
        $cmd = new Command($tokens);
        $cmd->useDefaultHelp(false);
        $return = $cmd->parse();
        $this->assertEquals(1, $return);
    }

    /**
     * Test error
     */
    public function testErrorWhenTrapThenNoException()
    {
        $tokens = ['filename', '-a', 'v1'];
        $cmd = new Command($tokens);
        $cmd->flag('a');
        $cmd->trapErrors();
        $this->assertTrue(true);
    }

    /**
     * Test print help sets flag
     */
    public function testWhenPrintHelpThenFlagSet()
    {
        $tokens = ['filename', '-a', 'v1'];
        $cmd = new Command($tokens);
        $cmd->flag('a');

        $printed = $cmd->didShowHelp();
        $this->assertFalse($printed);

        $cmd->printHelp();

        $printed = $cmd->didShowHelp();
        $this->assertTrue($printed);
    }
}
