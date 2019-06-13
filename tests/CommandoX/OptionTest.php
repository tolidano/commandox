<?php declare(strict_types = 1);

namespace CommandoX\Test;

use CommandoX\Option;

require_once dirname(dirname(__DIR__)) . '/vendor/autoload.php';

// PHPUnit version hack https://stackoverflow.com/questions/6065730/why-fatal-error-class-phpunit-framework-testcase-not-found-in
if (!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
}

class OptionTest extends \PHPUnit_Framework_TestCase
{
    public function testNamedOption()
    {
        $name = 'f';
        $option = new Option($name);
        $this->assertEquals($name, $option->getName());
    }

    public function testGetDescription()
    {
        $description = 'description';
        $option = new Option('f');
        $option->setDescription($description);
        $this->assertEquals($description, $option->getDescription());
    }

    public function testAnonymousOption()
    {
        $option = new Option(0);
        $name = 'f';
        $namedOption = new Option($name);
        $anonymousOption = new Option(1);

        $this->assertEquals(0, $option->getName());
        $this->assertEquals($name, $namedOption->getName());
        $this->assertEquals(1, $anonymousOption->getName());
    }

    public function testAnonymousOptionWithTitle()
    {
        $expected = 'test';
        $option = new Option(0);
        $option->setTitle($expected);

        $this->assertEquals($expected, $option->getName());
    }

    /**
     * Test adding multiple aliases
     *
     * @test
     */
    public function testAddAlias()
    {
        $name = 'f';
        $alias = 'foo';
        $alias2 = 'foobar';

        $option = new Option($name);
        $option->addAlias($alias);
        $option->addAlias($alias2);

        $this->assertEquals([$alias, $alias2], $option->getAliases());
    }

    /**
     * @dataProvider values
     */
    public function testSetValue($val)
    {
        $option = new Option('f');
        $option->setValue($val);
        $this->assertEquals($val, $option->getValue());
    }

    /**
     * @dataProvider values
     */
    public function testMap($val)
    {
        $option = new Option('f');
        $option->setMap(function ($value) {
            return $value . $value;
        });

        $option->setValue($val);
        $this->assertEquals($val . $val, $option->getValue());
    }

    /**
     * Ensure rules validate
     *
     * @test
     */
    public function testRuleValidate()
    {
        $option = new Option('f');
        $option->setRule(function ($value) {
            return is_numeric($value);
        });

        $this->assertFalse($option->validate('a'));
        $this->assertFalse($option->validate('abc'));
        $this->assertTrue($option->validate('2'));
        $this->assertTrue($option->validate(2));
        $this->assertTrue($option->validate(0));
    }

    /**
     * Test that an exception is thrown when set value does not validate
     */
    public function testRuleSetValueFailingValidationThenThrowsException()
    {
        $this->expectException(\Exception::class);

        $option = new Option('f');
        $option->setRule(function ($value) {
            return is_numeric($value);
        });
        $option->setValue('abc');
    }

    /**
     * Test file operations
     *
     * @test
     */
    public function testFile()
    {
        $file = dirname(__FILE__) . '/assets/example.txt';
        $option = new Option(0);
        $option->setFileRequirements(true, false);
        $option->setValue($file);

        $this->assertTrue($option->isFile());
        $files = $option->getValue();
        $actual = array_pop($files);
        $this->assertEquals($file, $actual);
    }

    /**
     * Test file globbing
     *
     * @test
     */
    public function testFileGlob()
    {
        $file = dirname(__FILE__) . '/assets/*.txt';
        $option = new Option(0);
        $option->setFileRequirements(true, true);
        $option->setValue($file);

        $file1 = dirname(__FILE__) . '/assets/example.txt';
        $file2 = dirname(__FILE__) . '/assets/another.txt';
        $file3 = dirname(__FILE__) . '/assets/markdown.md';

        $values = $option->getValue();
        $this->assertTrue($option->isFile());
        $this->assertCount(2, $values);
        $this->assertTrue(in_array($file1, $values));
        $this->assertTrue(in_array($file2, $values));
        $this->assertFalse(in_array($file3, $values));
    }

    /**
     * Test empty globs throw exception
     *
     * @test
     */
    public function testFileGlobWhenEmptyThenThrows()
    {
        $this->expectException(\Exception::class);
        $option = new Option(0);
        $option->setFileRequirements(true, true);
        $file = dirname(__FILE__) . '/assets/*.bad';
        $option->setValue($file);
    }

    /**
     * @dataProvider values
     */
    public function testDefault($val)
    {
        $option = new Option('f');
        $option->setDefault($val);
        $this->assertEquals($val, $option->getValue());
    }

    /**
     * Test that requires options are set correctly
     */
    public function testSetRequired()
    {
        $option = new Option('f');
        $option->setNeeds('foo');
        $this->assertTrue(in_array('foo', $option->getNeeds()));
    }

    /**
     * Test that the needed requirements are met
     */
    public function testOptionRequirementsMet()
    {
        $option = new Option('f');
        $option->setNeeds('foo');
        $neededOption = new Option('foo');
        $neededOption->setValue(true);
        $optionSet = [
            'foo' => $neededOption,
        ];

        $this->assertTrue($option->hasNeeds($optionSet));
    }

    /**
     * Test hasNeeds when requirements are not met.
     *
     * @test
     */
    public function testOptionRequiresNotMet()
    {
        $option = new Option('f');
        $option->setNeeds('foo');
        $optionSet = [
            'foo' => new Option('foo'),
        ];
        $expected = [
            'foo',
        ];
        $this->assertEquals($expected, $option->hasNeeds($optionSet));
    }

    /**
     * Test getHelp and __toString
     *
     * @test
     */
    public function testGetHelpIncludesAllComponents()
    {
        $testTitle = 'TestTitle';
        $testAlias = 'TestAlias';
        $testDescription = 'TestDescription';
        $testDefault = 'TestDefault';
        $testRequired = 'required';
        $option = new Option('t');
        $option->setTitle($testTitle)
               ->addAlias($testAlias)
               ->setDescription($testDescription)
               ->setRequired(true)
               ->setDefault($testDefault);
        $help = 'TO STRING ' . $option;

        $this->assertIsInt(strpos($help, $testTitle));
        $this->assertIsInt(strpos($help, $testAlias));
        $this->assertIsInt(strpos($help, $testDescription));
        $this->assertIsInt(strpos($help, $testRequired));
        $this->assertIsInt(strpos($help, $testDefault));
    }

    /**
     * Constructing with no parameter throws
     *
     * @test
     */
    public function testConstructWhenNoNameThenThrowsException()
    {
        $this->expectException(\Exception::class);
        new Option(null);
    }

    /**
     * Non-boolean values passed to boolean options should throw
     *
     * @test
     */
    public function testSetValueWhenBooleanNonBooleanValueThenThrowsException()
    {
        $this->expectException(\Exception::class);
        $option = new Option('t');
        $option->setBoolean();
        $option->setValue('x');
    }

    /**
     * Non-integer values passed to increment options should throw
     *
     * @test
     */
    public function testSetValueWhenIncrementNonIntegerValueThenThrowsException()
    {
        $this->expectException(\Exception::class);
        $option = new Option('t');
        $option->setIncrement(3);
        $option->setValue('x');
    }

    // Providers
    public function values()
    {
        return [
            ['abc'],
            ['The quick, brown fox jumps over a lazy dog.'],
            ['200'],
            [200],
            [0],
            [1.5],
            [0.0],
            [true],
            [false],
        ];
    }
}
