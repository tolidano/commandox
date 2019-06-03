<?php declare(strict_types = 1);
/**
 * @author Shawn Tolidano <shawn@tolidano.com>
 * @author Nate Good <me@nategood.com>
 */

namespace CommandoX;

use \CommandoX\Util\Terminal;

/**
 * Here are all the methods available through __call.
 * For accurate method documentation, see the actual method.
 *
 * This is merely for intellisense purposes!
 *
 * @method Option option (mixed $name = null)
 * @method Option o (mixed $name = null)
 * @method Option flag (string $name)
 * @method Option argument (mixed $option = null)
 * @method Option alias (string $alias)
 * @method Option a (string $alias)
 * @method Option aka (string $alias)
 * @method Option description (string $description)
 * @method Option d (string $description)
 * @method Option describe (string $description)
 * @method Option describedAs (string $description)
 * @method Option require (bool $require = true)
 * @method Option r (bool $require = true)
 * @method Option required (bool $require = true)
 * @method Option needs (mixed $options)
 * @method Option must (\Closure $rule)
 * @method Option cast (\Closure $map)
 * @method Option castTo (\Closure $map)
 * @method Option referToAs (string $name)
 * @method Option title (string $name)
 * @method Option referredToAs (string $name)
 * @method Option boolean ()
 * @method Option default (mixed $defaultValue)
 * @method Option defaultsTo (mixed $defaultValue)
 * @method Option file ()
 * @method Option expectsFile ()
 */

class Option
{
    private $aliases = []; /* aliases for this argument */
    private $boolean = false; /* bool */
    private $default = null; /* mixed default value for this option when no value is specified */
    private $description; /* string */
    private $file = false; /* bool */
    private $fileRequireExists = true; /* bool require that the file path is valid */
    private $fileAllowGlobbing = true; /* bool allow globbing for files */
    private $increment = false; /* bool */
    private $map = null; /* closure */
    private $maxValue = 0; /* int max value for increment */
    private $name = null; /* string optional name of argument */
    private $needs = []; /* set of other required options for this option */
    private $required = false; /* bool */
    private $rule = null; /* closure */
    private $title = null; /* a formal way to reference this argument */
    private $type = 0; /* int see constants */
    private $value = null; /* mixed */

    public const TYPE_SHORT = 1;
    public const TYPE_VERBOSE = 2;
    public const TYPE_NAMED = 3; // 1|2
    public const TYPE_ANONYMOUS = 4;

    /**
     * @param string|int $name single char name or int index for this option
     * @return Option
     * @throws \Exception
     */
    public function __construct($name)
    {
        if (!is_int($name) && empty($name)) {
            throw new \Exception(sprintf('Invalid option name %s: Must be identified by a single character or an integer', $name));
        }

        $this->type = self::TYPE_ANONYMOUS;
        if (!is_int($name)) {
            $this->type = mb_strlen($name, 'UTF-8') === 1 ? self::TYPE_SHORT : self::TYPE_VERBOSE;
        }

        $this->name = $name;
    }

    /**
     * Add an alias for this option
     *
     * @param string $alias
     * @return Option
     */
    public function addAlias(string $alias): Option
    {
        $this->aliases[] = $alias;
        return $this;
    }

    /**
     * Add a description for this option
     *
     * @param string $description
     * @return Option
     */
    public function setDescription(string $description): Option
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Set whether this option is a boolean flag
     *
     * @param bool $bool
     * @return Option
     */
    public function setBoolean(bool $bool = true): Option
    {
        // if we didn't define a default already, set false as the default value
        if ($this->default === null) {
            $this->setDefault(false);
        }
        $this->boolean = $bool;
        return $this;
    }

    /**
     * Set the maximum increment value for this flag (-vvvv)
     *
     * @param int $max
     * @return Option
     */
    public function setIncrement(int $max = 0): Option
    {
        // if we didn't define a default already, set 0 as the default value
        if ($this->default === null) {
            $this->setDefault(0);
        }
        $this->increment = true;
        $this->maxValue = $max;
        return $this;
    }

    /**
     * Require that the argument is a file.  This will
     * make sure the argument is a valid file, will expand
     * the file path provided to a full path (e.g. map relative
     * paths), and in the case where $allow_globbing is set,
     * supports file globbing and returns an array of matching
     * files.
     *
     * @param bool $requireExists
     * @param bool $allowGlobbing
     * @throws \Exception if the file does not exists
     * @return Option
     */
    public function setFileRequirements(bool $requireExists = true, bool $allowGlobbing = true): Option
    {
        $this->file = true;
        $this->fileRequireExists = $requireExists;
        $this->fileAllowGlobbing = $allowGlobbing;
        return $this;
    }

    /**
     * Set the title of this option
     *
     * @param string $title
     * @return Option
     */
    public function setTitle(string $title): Option
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set whether this option is required
     *
     * @param bool $bool required?
     * @return Option
     */
    public function setRequired(bool $bool = true): Option
    {
        $this->required = $bool;
        return $this;
    }

    /**
     * Set an option as required
     *
     * @param string $option Option name
     * @return Option
     */
    public function setNeeds(string $option): Option
    {
        if (!is_array($option)) {
            $option = [$option];
        }
        foreach ($option as $opt) {
            $this->needs[] = $opt;
        }
        return $this;
    }

    /**
     * Set the default value of this option
     * Immediately attempts to set the default value as the value
     *
     * @param mixed $value default value
     * @return Option
     */
    public function setDefault(/* mixed */ $value): Option
    {
        $this->default = $value;
        $this->setValue($value);
        return $this;
    }

    /**
     * @return mixed
     */
    public function getDefault()
    {
        return $this->default;
    }

    /**
     * @param \Closure|string $rule regex, closure
     * @return Option
     */
    public function setRule(/* mixed */ $rule): Option
    {
        $this->rule = $rule;
        return $this;
    }

    /**
     * @param \Closure $map
     * @return Option
     */
    public function setMap(\Closure $map): Option
    {
        $this->map = $map;
        return $this;
    }

    /**
     * @param \Closure|string $value regex, closure
     * @return mixed
     */
    public function map(/* mixed */ $value)
    {
        if (!is_callable($this->map)) {
            return $value;
        }

        // TODO add int, float and regex special case

        // TODO double check syntax
        return call_user_func($this->map, $value);
    }


    /**
     * Validate the supplied value against the rule
     *
     * @param mixed $value
     * @return bool
     */
    public function validate(/* mixed */ $value): bool
    {
        if (!is_callable($this->rule)) {
            return true;
        }

        // TODO add int, float and regex special case

        // TODO double check syntax
        return call_user_func($this->rule, $value);
    }

    /**
     * Parse a file path from a file argument
     *
     * @param string $filePath
     * @return array single element array of full file path
     *      or an array of file paths if "globbing" is supported
     */
    public function parseFilePath(string $filePath): array
    {
        $path = realpath($filePath);
        if ($this->fileAllowGlobbing) {
            $files = glob($filePath);
            if (empty($files)) {
                return $files;
            }
            return array_map(function ($file) {
                return realpath($file);
            }, $files);
        }

        return [$path];
    }

    /**
     * @return string|int name of the option
     */
    public function getName()
    {
        return ($this->title && is_numeric($this->name)) ? $this->title : $this->name;
    }

    /**
     * @return string description of the option
     */
    public function getDescription(): string
    {
        return $this->description;
    }

    /**
     * @return int type (see OPTION_TYPE_CONST)
     */
    public function getType(): int
    {
        return $this->type;
    }

    /**
     * @return mixed value of the option
     */
    public function getValue()
    {
        return $this->value;
    }

    /**
     * @return string[] list of aliases
     */
    public function getAliases(): array
    {
        return $this->aliases;
    }

    /**
     * Get the current set of this option's requirements
     *
     * @return string[] List of required options
     */
    public function getNeeds(): array
    {
        return $this->needs;
    }

    /**
     * @return bool is this option a boolean
     */
    public function isBoolean(): bool
    {
        return $this->boolean;
    }

    /**
     * @return bool is this option an incremental option
     */
    public function isIncrement(): bool
    {
        return $this->increment;
    }

    /**
     * @return bool is this option a boolean
     */
    public function isFile(): bool
    {
        return $this->file;
    }

    /**
     * @return bool is this option required?
     */
    public function isRequired(): bool
    {
        return $this->required;
    }

    /**
     * Check to see if requirements list for option are met
     *
     * @param array $optionsList Set of current options defined
     * @return boolean|array True if requirements met, array if not found
     */
    public function hasNeeds(array $optionsList)
    {
        $needs = $this->getNeeds();

        $definedOptions = array_keys($optionsList);
        $notFound = [];
        foreach ($needs as $need) {
            if (!in_array($need, $definedOptions)) {
                // The needed option has not been defined as a valid flag.
                $notFound[] = $need;
            } elseif (!$optionsList[$need]->getValue()) {
                // The needed option has been defined as a valid flag, but was
                // not pased in by the user.
                $notFound[] = $need;
            }
        }

        return (empty($notFound)) ? true : $notFound;
    }

    /**
     * @param mixed $value for this option (set on the command line)
     * @throws \Exception
     */
    public function setValue($value)
    {
        if ($this->isBoolean() && !is_bool($value)) {
            throw new \Exception(sprintf('Boolean option expected for option %s, received %s value instead', $this->name, $value));
        }
        if (!$this->validate($value)) {
            throw new \Exception(sprintf('Invalid value, %s, for option %s', $value, $this->name));
        }
        if ($this->isIncrement()) {
            if (!is_int($value)) {
                throw new \Exception(sprintf('Integer expected as value for %s, received %s instead', $this->name, $value));
            }
            if ($value > $this->maxValue && $this->maxValue > 0) {
                $value = $this->maxValue;
            }
        }
        if ($this->isFile()) {
            $filePath = $this->parseFilePath($value);
            if (empty($filePath) && $this->fileRequireExists) {
                throw new \Exception(sprintf('Expected %s to be a valid file', $value, $this->name));
            }
            $value = $filePath;
        }
        $this->value = $this->map($value);
    }

    /**
     * Provide the help text for this option
     *
     * @return string
     */
    public function getHelp(): string
    {
        $color = new \Colors\Color();
        $isNamed = $this->type & self::TYPE_NAMED;
        $help = (empty($this->title) ? "arg {$this->name}" : $this->title) . PHP_EOL;

        if ($isNamed) {
            $help =  PHP_EOL . (mb_strlen($this->name, 'UTF-8') === 1 ?
                '-' : '--') . $this->name;
            if (!empty($this->aliases)) {
                foreach ($this->aliases as $alias) {
                    $help .= (mb_strlen($alias, 'UTF-8') === 1 ? '/-' : '/--') . $alias;
                }
            }
            if (!$this->isBoolean() && !$this->isIncrement()) {
                $help .= ' ' . $color->underline('<argument>');
            }
            $help .= PHP_EOL;
        }

        // bold what has been displayed so far
        $help = $color->bold($help);

        $titleLine = '';
        if ($isNamed && $this->title) {
            $titleLine .= $this->title . '.';
            if ($this->isRequired()) {
                $titleLine .= ' ';
            }
        }

        if ($this->isRequired()) {
            $titleLine .= $color->red('required.');
        }

        if ($titleLine) {
            $titleLine .= ' ';
        }

        $description = $titleLine . $this->description;

        if ($this->default) {
            $description .= ' (default: ' . $this->default . ')';
        }

        if (!empty($description)) {
            $descriptionArray = explode(PHP_EOL, trim($description));
            foreach ($descriptionArray as $descriptionLine) {
                $help .= Terminal::wrap($descriptionLine, 5, 1) . PHP_EOL;
            }
        }

        return $help;
    }

    /**
     * @return string
     */
    public function __toString(): string
    {
        return $this->getHelp();
    }
}
