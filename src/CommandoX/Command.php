<?php
/**
 * @author Shawn Tolidano <shawn@tolidano.com>
 * @author Nate Good <me@nategood.com>
 * @method \CommandoX\Command option (string $name)
 * @method \CommandoX\Command flag (string $name)
 * @method \CommandoX\Command argument (int $index)
 * @method \CommandoX\Command boolean (bool $boolean=true)
 * @method \CommandoX\Command require(bool $require=true)
 * @method \CommandoX\Command alias(string $alias)
 * @method \CommandoX\Command title(string $title)
 * @method \CommandoX\Command describe(string $description)
 * @method \CommandoX\Command map(callable $callback)
 * @method \CommandoX\Command must(callable $callback)
 * @method \CommandoX\Command needs(string $name)
 * @method \CommandoX\Command file(bool $require_exists=true,bool $allow_globbing=false)
 * @method \CommandoX\Command default($value)
 */

namespace CommandoX;

use CommandoX\Option\TypeEnum;
use CommandoX\Util\Terminal;

/**
 * Here are all the methods available through __call.
 * For accurate method documentation, see the actual method.
 *
 * This is merely for intellisense purposes!
 *
 * @method \CommandoX\Command option (mixed $name = null)
 * @method \CommandoX\Command o (mixed $name = null)
 * @method \CommandoX\Command flag (string $name)
 * @method \CommandoX\Command argument (mixed $option = null)
 * @method \CommandoX\Command alias (string $alias)
 * @method \CommandoX\Command a (string $alias)
 * @method \CommandoX\Command aka (string $alias)
 * @method \CommandoX\Command description (string $description)
 * @method \CommandoX\Command d (string $description)
 * @method \CommandoX\Command describe (string $description)
 * @method \CommandoX\Command describedAs (string $description)
 * @method \CommandoX\Command require (bool $require = true)
 * @method \CommandoX\Command r (bool $require = true)
 * @method \CommandoX\Command required (bool $require = true)
 * @method \CommandoX\Command needs (mixed $options)
 * @method \CommandoX\Command must (\Closure $rule)
 * @method \CommandoX\Command map (\Closure $map)
 * @method \CommandoX\Command cast (\Closure $map)
 * @method \CommandoX\Command castTo (\Closure $map)
 * @method \CommandoX\Command referToAs (string $name)
 * @method \CommandoX\Command title (string $name)
 * @method \CommandoX\Command referredToAs (string $name)
 * @method \CommandoX\Command boolean ()
 * @method \CommandoX\Command default (mixed $defaultValue)
 * @method \CommandoX\Command defaultsTo (mixed $defaultValue)
 * @method \CommandoX\Command file ()
 * @method \CommandoX\Command expectsFile ()
 */

class Command implements \ArrayAccess, \Iterator
{
    private $addedHelp = false;
    private $arguments = [];
    private $currentOption = null;
    private $errorBeep = true;
    private $errorTrap = true;
    private $help = null;
    private $name = null;
    private $namelessOptionCounter = 0;
    private $parsed = false;
    private $position = 0;
    private $sortedKeys = [];
    private $tokens = [];
    private $defaultHelp = true;
    private $showedHelp = false;

    /**
     * @var Option[]
     */
    private $flags = [];

    /**
     * @var Option[]
     */
    private $options = [];

    /**
     * @var array Valid "option" options, mapped to their aliases
     */
    public static $methods = [
        'option' => 'option',
        'o' => 'option',

        'flag' => 'flag',
        'argument' => 'argument',

        'boolean' => 'boolean',
        'bool' => 'boolean',
        'b' => 'boolean',

        'require' => 'require',
        'required' => 'require',
        'r' => 'require',

        'alias' => 'alias',
        'aka' => 'alias',
        'a' => 'alias',

        'title' => 'title',
        'referToAs' => 'title',
        'referredToAs' => 'title',

        'describe' => 'describe',
        'd' => 'describe',
        'describeAs' => 'describe',
        'description' => 'describe',
        'describedAs' => 'describe',

        'map' => 'map',
        'mapTo' => 'map',
        'cast' => 'map',
        'castWith' => 'map',

        'increment' => 'increment',
        'repeatable' => 'increment',
        'repeats' => 'increment',
        'count' => 'increment',

        'must' => 'must',
        'needs' => 'needs',

        'file' => 'file',
        'expectsFile' => 'file',

        'default' => 'default',
        'defaultsTo' => 'default',
    ];

    /**
     * @param array|null $tokens defaults to $argv
     */
    public function __construct(array $tokens = null)
    {
        if (empty($tokens)) {
            $tokens = $_SERVER['argv'];
        }

        $this->setTokens($tokens);
    }

    /**
     * Ensure parsing has occurred before destroying the command
     */
    public function __destruct()
    {
        if (!$this->parsed) {
            $this->parse();
        }
    }

    /**
     * Factory style for builders
     *
     * @param array $tokens
     * @return Command or subclass of Command
     */
    public static function define($tokens = null)
    {
        return new static($tokens);
    }

    /**
     * This is the core of Command.  Any time we are operating on
     * an individual option for a command (e.g. $cmd->option()->require()...)
     * it relies on this magic method.  It allows us to handle some logic
     * that is applicable across the board and also allows easy aliasing of
     * methods (e.g. "o" for "option"). Since it is a CLI library, such
     * minified aliases are appropriate.
     *
     * @param string $name
     * @param array  $arguments
     *
     * @return Command
     * @throws \Exception
     */
    public function __call($name, array $arguments)
    {
        if (empty(self::$methods[$name])) {
            throw new \Exception(sprintf('Unknown function, %s, called', $name));
        }

        // use the fully quantified name, e.g. "option" when "o"
        $name = self::$methods[$name];

        // set the option we'll be acting on
        if (empty($this->currentOption) && !in_array($name, ['option', 'flag', 'argument'])) {
            throw new \Exception(sprintf('Invalid Option Chain: Attempting to call %s before an "option" declaration', $name));
        }

        array_unshift($arguments, $this->currentOption);
        call_user_func_array([$this, "_$name"], $arguments);

        return $this;
    }

    /**
     * @param Option|null $option
     * @param string|int $name
     * @return Option
     *
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    private function _option($option, $name = null)
    {
        // Is this a previously declared option?
        if (isset($name) && !empty($this->options[$name])) {
            $this->currentOption = $this->getOption($name);
            return $this->currentOption;
        }
        if (!isset($name)) {
            $name = $this->namelessOptionCounter++;
        }
        $this->currentOption = $this->options[$name] = new Option($name);
        return $this->currentOption;
    }

    /**
     * Like _option but only for named flags
     *
     * @param Option|null $option
     * @param string $name
     *
     * @throws \Exception
     * @return Option
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _flag($option, $name)
    {
        if (is_numeric($name)) {
            throw new \Exception('Attempted to reference flag with a numeric index');
        }
        return $this->_option($option, $name);
    }

    /**
     * Like _option but only for anonymous arguments

     * @param Option|null $option
     * @param int $index [optional] only used when referencing an existing option
     *
     * @throws \Exception
     * @return Option
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _argument($option, $index = null)
    {
        if (isset($index) && !is_numeric($index)) {
            throw new \Exception('Attempted to reference argument with a string name');
        }
        return $this->_option($option, $index);
    }

    /**
     * Set an option to be a boolean flag
     *
     * @param Option $option
     * @param bool $boolean [optional]
     * @return Option
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _boolean($option, $boolean = true)
    {
        return $option->setBoolean($boolean);
    }

    /**
     * Set an option to be required
     *
     * @param Option $option
     * @param bool $require [optional]
     * @return Option
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _require($option, $require = true)
    {
        return $option->setRequired($require);
    }

    /**
     * Set a requirement on an option
     *
     * @param Option $option Current option
     * @param string $name Name of option
     * @return Option instance
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _needs($option, $name)
    {
        return $option->setNeeds($name);
    }

    /**
     * Add an alias for an option
     *
     * @param Option $option
     * @param string $alias
     * @return Option
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _alias($option, $alias)
    {
        $this->options[$alias] = $this->currentOption;
        return $option->addAlias($alias);
    }

    /**
     * Set the description of an option (used in help text)
     *
     * @param Option $option
     * @param string $description
     * @return Option
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _describe($option, $description)
    {
        return $option->setDescription($description);
    }

    /**
     * Set a title, primarily for anonymous arguements in help text
     *
     * @param Option $option
     * @param string $title
     * @return Option
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _title($option, $title)
    {
        return $option->setTitle($title);
    }

    /**
     * Enforces a validation function on setting an option value
     *
     * @param Option $option
     * @param \Closure $callback (string $value) -> boolean
     * @return Option
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _must($option, \Closure $callback)
    {
        return $option->setRule($callback);
    }

    /**
     * Maps an option value to any mixed value
     *
     * @param Option $option
     * @param \Closure $callback
     * @return Option
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _map($option, \Closure $callback)
    {
        return $option->setMap($callback);
    }

    /**
     * Set the maximum increment value for a flag (i.e. -vvv)
     * Does not error, but will ignore additional values above $max
     *
     * @param Option $option
     * @param int $max
     * @return Option
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _increment($option, $max = 0)
    {
        return $option->setIncrement($max);
    }

    /**
     * Set the default value of an option
     * For booleans, the presence of the flag negates this value
     *
     * @param $option Option
     * @param mixed $value
     * @return Option
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _default($option, /* mixed */ $value)
    {
        return $option->setDefault($value);
    }

    /**
     * @param Option $option
     * @param bool   $require_exists
     * @param bool   $allow_globbing
     * @return Option
     *
     * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
     */
    private function _file($option, $requireExists = true, $allowGlobbing = false)
    {
        return $option->setFileRequirements($requireExists, $allowGlobbing);
    }

    /**
     * Whether to include the default help screen when --help is passed
     *
     * @param bool $help
     */
    public function useDefaultHelp($help = true)
    {
        $this->defaultHelp = $help;
    }

    /**
     * Rare that you would need to use this other than for testing,
     * allows defining the cli tokens, instead of using $argv
     *
     * @param array $cli_tokens
     * @return Command
     */
    public function setTokens(array $cli_tokens)
    {
        $this->tokens = $cli_tokens;
        return $this;
    }

    /**
     * Attempt to parse if parsing has not occurred
     *
     * @throws \Exception
     * @return int
     */
    private function parseIfNotParsed()
    {
        if ($this->isParsed()) {
            return 0;
        }
        return $this->parse();
    }

    /**
     * Explicitly parse the tokens
     *
     * @throws \Exception
     * @return int
     */
    public function parse()
    {
        $this->parsed = true;
        $this->attachHelp();

        try {
            $tokens = $this->tokens;
            // the executed filename
            $this->name = array_shift($tokens);

            $keyvals = [];
            $count = 0; // standalone argument count

            while (!empty($tokens)) {
                $token = array_shift($tokens);

                list($name, $type) = $this->_parseOption($token);
                // We allow short groups
                if (strlen($name) > 1 && $type === TypeEnum::SHORT) {
                    $group = str_split($name);
                    // correct option name
                    $name = array_shift($group);

                    // Iterate in reverse order to keep the option order correct
                    // options that don't require an argument can be mixed.
                    foreach (array_reverse($group) as $nextShort) {
                        // put it back into $tokens for another loop
                        array_unshift($tokens, "-{$nextShort}");
                    }
                }

                if ($type === TypeEnum::ARGUMENT) {
                    // it is an argument, use an int as the index
                    $keyvals[$count] = $name;

                    // We allow for "dynamic" anonymous arguments, so we
                    // add an option for any anonymous arguments that
                    // weren't predefined
                    if (!$this->hasOption($count)) {
                        $this->options[$count] = new Option($count);
                    }

                    $count++;
                } else {
                    // Short circuit if a valid help flag was set and we're using default help
                    if ($this->defaultHelp === true && ($name === 'help' || $name === 'h')) {
                        $this->printHelp();
                        return 0;
                    }

                    $option = $this->getOption($name);
                    if ($option->isBoolean()) {
                        $keyvals[$name] = !$option->getDefault();// inverse of the default, as expected
                    } elseif ($option->isIncrement()) {
                        if (!isset($keyvals[$name])) {
                            $keyvals[$name] = $option->getDefault() + 1;
                        } else {
                            $keyvals[$name]++;
                        }
                    } else {
                        // the next token MUST be an "argument" and not another flag/option
                        $token = array_shift($tokens);
                        list($val, $type) = $this->_parseOption($token);
                        if ($type !== TypeEnum::ARGUMENT) {
                            throw new \Exception(sprintf('Unable to parse option %s: Expected an argument', $token));
                        }
                        $keyvals[$name] = $val;
                    }
                }
            }

            // Set values (validates and performs map when applicable)
            foreach ($keyvals as $key => $value) {
                $this->getOption($key)->setValue($value);
            }

            // TODO protect against duplicates caused by aliases
            foreach ($this->options as $option) {
                if (is_null($option->getValue()) && $option->isRequired()) {
                    throw new \Exception(sprintf(
                        'Required %s %s must be specified',
                        $option->isNamed() ? 'option' : 'argument',
                        $option->getName()
                    ));
                }
            }

            // See if our options have what they require
            foreach ($this->options as $option) {
                $needs = $option->hasNeeds($this->options);
                if ($needs !== true) {
                    throw new \InvalidArgumentException(
                        'Option "' . $option->getName() . '" does not have required option(s): ' . implode(', ', $needs)
                    );
                }
            }

            // keep track of our argument vs. flag keys
            // done here to allow for flags/arguments added
            // at run time. Acceptable because option values are
            // not mutable after parsing.
            foreach ($this->options as $k => $v) {
                if (is_numeric($k)) {
                    $this->arguments[$k] = $v;
                } else {
                    $this->flags[$k] = $v;
                }
            }

            // Used in the \Iterator implementation
            $this->sortedKeys = array_keys($this->options);
            natsort($this->sortedKeys);
        } catch (\Exception $exception) {
            return $this->error($exception);
        }
        return 0;
    }

    /**
     * Causes terminal to beep and throws or traps the exception
     *
     * @param \Exception $e
     * @throws \Exception
     * @return int exit code
     */
    public function error($exception)
    {
        if ($this->errorBeep === true) {
            Terminal::beep();
        }

        if ($this->errorTrap !== true) {
            throw $exception;
        }

        echo $this->createTerminalError($exception->getMessage()) . PHP_EOL;
        return 1;
    }

    /**
     * Format an error message for the terminal consistently
     *
     * @param string $message
     * @return string
     */
    public function createTerminalError($message)
    {
        $color = new \Colors\Color();
        $error = sprintf('ERROR: %s ', $message);
        return $color($error)->bg('red')->bold()->white();
    }

    /**
     * Has this Command instance parsed its arguments?
     *
     * @return bool
     */
    public function isParsed()
    {
        return $this->parsed;
    }

    /**
     * @param string $token
     *
     * @throws \Exception
     * @return array [option name/value, OPTION_TYPE_*]
     */
    private function _parseOption($token)
    {
        $matches = [];
        if (substr($token, 0, 1) === '-' && !preg_match('/(?P<hyphen>\-{1,2})(?P<name>[a-z][a-z0-9_-]*)/i', $token, $matches)) {
            throw new \Exception(sprintf('Unable to parse option %s: Invalid syntax', $token));
        }

        if (!empty($matches['hyphen'])) {
            $type = (strlen($matches['hyphen']) === 1) ?
                TypeEnum::SHORT:
                TypeEnum::LONG;
            return [$matches['name'], $type];
        }

        return [$token, TypeEnum::ARGUMENT];
    }


    /**
     * @param string $option
     * @throws \Exception if $option does not exist
     * @return Option
     */
    public function getOption($option)
    {
        if (!$this->hasOption($option)) {
            throw new \Exception(sprintf('Unknown option, %s, specified', $option));
        }

        return $this->options[$option];
    }

    /**
     * @return array of `Option`s
     */
    public function getOptions()
    {
        $this->parseIfNotParsed();
        return $this->options;
    }

    /**
     * @return array of argument `Option` only
     */
    public function getArguments()
    {
        $this->parseIfNotParsed();
        return $this->arguments;
    }

    /**
     * @return array of flag `Option` only
     */
    public function getFlags()
    {
        $this->parseIfNotParsed();
        return $this->flags;
    }

    /**
     * If your command was `php filename -f flagvalue argument1 argument2`
     * `getArguments` would return array("argument1", "argument2");
     *
     * @return array of argument values only
     */
    public function getArgumentValues()
    {
        $this->parseIfNotParsed();

        $arguments = array_filter($this->arguments, function (Option $argument) {
            $argumentValue = $argument->getValue();
            return isset($argumentValue);
        });

        return array_map(function (Option $argument) {
            return $argument->getValue();
        }, $arguments);
    }

    /**
     * If your command was `php filename -f flagvalue argument1 argument2`
     * `getFlags` would return array("-f" => "flagvalue");
     *
     * @return array of flag values only
     */
    public function getFlagValues()
    {
        $this->parseIfNotParsed();
        return array_map(function (Option $flag) {
            return $flag->getValue();
        }, $this->dedupeFlags());
    }

    /**
     * @return array of deduped flag Options.  Needed because of
     *    how the flags are mapped internally to make alias lookup
     *    simpler/faster.
     */
    private function dedupeFlags()
    {
        $seen = [];
        foreach ($this->flags as $flag) {
            if (empty($seen[$flag->getName()])) {
                $seen[$flag->getName()] = $flag;
            }
        }
        return $seen;
    }

    /**
     * @param string $option name (named option) or index (anonymous option)
     * @return boolean
     */
    public function hasOption($option)
    {
        return !empty($this->options[$option]);
    }

    /**
     * @return string dump values
     */
    public function __toString()
    {
        // TODO return values of set options as map of option name => value
        return $this->getHelp();
    }

    /**
     * @return int
     */
    public function getSize()
    {
        return count($this->options);
    }

    /**
     * @param string $help
     * @return Command
     */
    public function setHelp($help)
    {
        $this->help = $help;
        return $this;
    }

    /**
     * @param bool $trap when true, exceptions will be caught by Commando and
     *    printed cleanly to standard error.
     * @return Command
     */
    public function trapErrors($trap = true)
    {
        $this->errorTrap = $trap;
        return $this;
    }

    /**
     * @return Command
     */
    public function doNotTrapErrors()
    {
        return $this->trapErrors(false);
    }

    /**
     * Terminal beep on error
     * @param bool $beep
     * @return Command
     */
    public function beepOnError($beep = true)
    {
        $this->errorBeep = $beep;
        return $this;
    }

    /**
     * @return string help docs
     */
    public function getHelp()
    {
        $this->attachHelp();

        if (empty($this->name) && isset($this->tokens[0])) {
            $this->name = $this->tokens[0];
        }

        $color = new \Colors\Color();

        $help = '';

        $help .= $color(Terminal::header(' ' . $this->name))
            ->white()->bg('green')->bold() . PHP_EOL;

        if (!empty($this->help)) {
            $help .= PHP_EOL . Terminal::wrap($this->help)
                . PHP_EOL;
        }

        $help .= PHP_EOL;

        $seen = [];
        $keys = array_keys($this->options);
        natsort($keys);
        foreach ($keys as $key) {
            $option = $this->getOption($key);
            if (in_array($option, $seen)) {
                continue;
            }
            $help .= $option->getHelp() . PHP_EOL;
            $seen[] = $option;
        }

        return $help;
    }

    /**
     * Whether or not the help screen has been printed
     *
     * @return boolean
     */
    public function didShowHelp()
    {
        return $this->showedHelp;
    }

    /**
     * Shortcut to print the help text
     *
     * @return void
     */
    public function printHelp()
    {
        $this->showedHelp = true;
        print($this->getHelp());
    }

    /**
     * If defaultHelp is true, attach the -h/--help option
     *
     * @return void
     */
    private function attachHelp()
    {
        if ($this->defaultHelp && !$this->addedHelp) {
            // Add in a default help method and help flag
            $this->option('h')
                ->aka('help')
                ->describe('Show the help page for this command.')
                ->boolean();
            $this->addedHelp = true;
        }
    }

    /**
     * @param string $offset
     *
     * @see \ArrayAccess
     * @return bool
     */
    public function offsetExists($offset)
    {
        return isset($this->options[$offset]);
    }

    /**
     * @param string $offset
     *
     * @see \ArrayAccess
     * @return mixed
     */
    public function offsetGet($offset)
    {
        // Support implicit/lazy parsing
        $this->parseIfNotParsed();
        if (!isset($this->options[$offset])) {
            return null; // follows normal php convention
        }
        return $this->options[$offset]->getValue();
    }

    /**
     * @param string $offset
     * @param string $value
     * @throws \Exception
     * @see \ArrayAccess
     */
    public function offsetSet($offset, $value)
    {
        throw new \Exception("Setting an option $offset to value $value via array syntax is not permitted");
    }

    /**
     * @param string $offset
     * @see \ArrayAccess
     */
    public function offsetUnset($offset)
    {
        $this->options[$offset]->setValue(null);
    }

    /**
     * @see \Iterator
     */
    public function rewind()
    {
        $this->position = 0;
    }

    /**
     * @see \Iterator
     * @return mixed value of current option
     */
    public function current()
    {
        return $this->options[$this->sortedKeys[$this->position]]->getValue();
    }

    /**
     * @see \Iterator
     * @return int
     */
    public function key()
    {
        return $this->position;
    }

    /**
     * @see \Iterator
     */
    public function next()
    {
        ++$this->position;
    }

    /**
     * @see \Iterator
     * @return bool
     */
    public function valid()
    {
        return isset($this->sorted_keys[$this->position]);
    }
}
