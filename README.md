# CommandoX
## PHP 7.2+ CLI Manager

[![Build Status](https://secure.travis-ci.org/tolidano/commandox.png?branch=master)](http://travis-ci.org/tolidano/commandox)

CommandoX is a PHP command line interface library that beautifies and simplifies writing PHP scripts intended for command line use.
This is a PHP 5.4-7.1 port of Nate Good's abandoned Commando project available here: (https://www.github.com/nategood/commando)

## Why?

PHP's `$argv` magic variable and global `$_SERVER['argv']` should be avoided, encapsulated, or abstracted away in proper OOP design.
PHP's [`getopt`](http://php.net/manual/en/function.getopt.php) is not a significant improvement, and most other PHP CLI libraries are far too OOP bloated.  CommandoX provides a clean and readable interface without a ton of overhead or common boilerplate when it comes to handling CLI input.

## Installation

*CommandoX requires that you are running PHP 5.4 - PHP 7.1.*

CommandoX is [PSR-0](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-0.md) compliant and can be installed using [Composer](http://getcomposer.org/).  Add `tolidano/commandox` to your `composer.json`

    "require": {
        "tolidano/commandox": "*"
    }

If you're new to Composer...

 - [Download and build Composer](http://getcomposer.org/download/)
 - Make it [globally accessible](http://getcomposer.org/doc/00-intro.md#globally)
 - `cd` to your the directory where you'll be writing your CommandoX script and run `composer install`

*Installing via Composer is the only supported option.*

## Example

Here is an example of a PHP CommandoX script that gives a decent tour of CommandoX's features:

``` php
<?php

require_once 'vendor/autoload.php';

$hello_cmd = new CommandoX\Command();

// Define first option
$hello_cmd->option()
    ->require()
    ->describedAs("A person's name");

// Define a flag "-t" aka "--title"
$hello_cmd->option('t')
    ->aka('title')
    ->describedAs('When set, use this title to address the person')
    ->must(function($title) {
        $titles = ['Mister', 'Mr', 'Misses', 'Mrs', 'Miss', 'Ms'];
        return in_array($title, $titles);
    })
    ->map(function($title) {
        $titles = ['Mister' => 'Mr.', 'Misses' => 'Mrs.', 'Miss' => 'Ms.'];
        if (array_key_exists($title, $titles)) {
            $title = $titles[$title];
        }
        return $title;
    });

// Define a boolean flag "-c" aka "--capitalize"
$hello_cmd->option('c')
    ->aka('capitalize')
    ->aka('cap')
    ->describedAs('Always capitalize the words in a name')
    ->boolean();

// Define an incremental flag "-e" aka "--educate"
$hello_cmd->option('e')
    ->aka('educate')
    ->map(function ($value) {
        $postfix = ['', 'Jr.', 'Esq.', 'PhD'];
        return $postfix[$value];
    })
    ->count(4);

$name = $hello_cmd['capitalize'] ? ucwords($hello_cmd[0]) : $hello_cmd[0];

print("Hello {$hello_cmd['title']} $name {$hello_cmd['educate']}!" . PHP_EOL);
```

Running it (save the above script to `hello.php`):

    > php hello.php Shawn
    Hello, Shawn!

    > php hello.php --capitalize shawn
    Hello, Shawn!

    > php hello.php -c -t Mr 'shawn tolidano'
    Hello, Mr. Shawn Tolidano!

    > php hello.php -ceet Mr 'shawn tolidano'
    Hello, Mr. Shawn Tolidano Esq.!

Things to note:

 - CommandoX implements \ArrayAccess so it acts like an array when you want to retrieve values from it
 - For "anonymous" (i.e. not a named option) arguments, access them based on their numeric index
 - We can access option values via the flag's name or it's alias
 - We can use closures to perform validation (`->must()`) and map operations (`->map()`) as part of the option definition

## Automatic Help

CommandoX has automatic `-h/--help` support built in.  Calling your script with this flag (either `-h` or `--help`) will print out a help page based on your option definitions and CommandoX settings.  If you define an option with the name or alias of `h` or `help`, it will override this built in support.

![help screenshot](http://cl.ly/image/1y3i2m2h220u/Screen%20Shot%202012-08-19%20at%208.54.49%20PM.png)

## Error Messaging

By default, CommandoX will catch Exceptions that occur during the parsing process. Instead, CommandoX prints a formatted, user-friendly error message to standard error and exits with a code of 1. If you wish to have CommandoX throw Exceptions instead, call the `doNotTrapErrors` method on your Command instance.

![error screenshot](http://f.cl.ly/items/150H2d3x0l3O3J0s3i1G/Screen%20Shot%202012-08-19%20at%209.58.21%20PM.png)

## Command Methods

These options work on the "command" level.

### `useDefaultHelp (bool help)`

The default behavior of CommandoX is to provide a `--help` option that spits out a useful help page generated off of your option definitions.  Disable this feature by calling `useDefaultHelp(false)`

### `setHelp (string help)`

Text to prepend to the help page.  Use this to describe the command at a high level and provide some examples usages of the command.

### `printHelp()`

Print the default help for the command. Useful if you want to output help if no arguments are passed.

### `beepOnError (bool beep=true)`

When an error occurs, print character to make the terminal "beep".

### `getOptions`

Return an array of `Options` for each option provided to the command.

### `getFlags`

Return an array of `Options` for only the flags provided to the command.

### `getArguments`

Return an array of `Options` for only the arguments provided to the command.
The order of the array is the same as the order of the arguments.

### `getFlagValues`

Return associative array of values for arguments provided to the command.  E.g. `['f' => 'value1']`.

### `getArgumentValues`

Return array of values for arguments provided to the command. E.g. `['value1', 'value2']`.

## Command Option Definition Methods

These options work on the "option" level, even though they are chained to a `Command` instance

### `option (mixed $name = null)`

Aliases: `o`

Define a new option.  When `name` is set, the option will be a named "flag" option.  Can be a short form option (e.g. `f` for option `-f`) or long form (e.g. `foo` for option `--foo`).  When no `name` is defined, the option is an anonymous argument and is referenced by its ordinal position.

### `flag (string $name)`

Same as `option` except that it can only be used to define "flag" type options (aka those options that must be specified with a `-flag` on the command line).

### `argument ()`

Same as `option` except that it can only be used to define "argument" type options (aka those options that are specified WITHOUT a `-flag` on the command line).

### `alias (string $alias)`

Aliases: `a`, `aka`

Add an alias for a named option.  This method can be called multiple times to add multiple aliases.

### `description (string $description)`

Aliases: `d`, `describe`, `describedAs`

Text to describe this option.  This text will be used to build the "help" page.

### `require (bool $require)`

Aliases: `r`, `required`

Require that this flag is specified.

### `needs (string|array $options)`

Aliases: none

Require that other `$options` be set for this option to be used.

### `must (Closure $rule)`

Aliases: none

Define a rule to validate input against.  Takes function that accepts a string `$value` and returns a boolean as to whether or not `$value` is valid.

### `map (Closure $map)`

Aliases: `cast`, `castTo`

Perform a map operation on the value for this option.  Takes function that accepts a string `$value` and returns mixed (map to any type).

### `referToAs (string $name)`

Aliases: `title`, `referredToAs`

Add a name to refer to an argument option by.  Makes help and error messages cleaner for anonymous "argument" options.

### `boolean ()`

Aliases: none

Specifices that the flag is a boolean type flag.

### `increment (int $max)`

Aliases: `i`, `count`, `repeats`, `repeatable`

Specifies that the flag is a counter flag. The value of the flag will be incremented up to the value of `$max` for each time the flag is used in the command. Options that are set to `increment` or `boolean` types can be grouped together as a single option.

### `default (mixed $defaultValue)`

Aliases: `defaultsTo`

If the value is not specified, default to `$defaultValue`.

In the specific case of `boolean()` flags, when the flag is present, the value of this option is the negation of `$defaultValue`.
That is to say, if you have a flag -b with a default of `true`, when -b is present, the value of the option will be `false`.

### `file ()`

Aliases: `expectsFile`

The value specified for this option must be a valid file path. When used, relative paths will be converted into fully-qualified file paths and globbing is also optionally supported.  See the file.php example.

## Contributing

CommandoX encourages pull requests.  When submitting a pull request:

 - Run composer install to pull in require-dev dependencies (`composer update`)
 - Install the pre-commit hook (`./scripts/installHook.sh` to automatically check your code)
 - Target the `master` branch on your PR
 - Follow the [PSR-1](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-1-basic-coding-standard.md) and [PSR-2](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-2-coding-style-guide.md) coding standards
 - Add appropriate test coverage for your change(s) (coverage must remain over 90%)
 - Run all unit tests from the tests directory via `phpunit` (install phpunit 8+ globally with composer)
 - Include commenting where appropriate
 - Add a descriptive PR message, preferably referencing a GitHub issue
 - Only security and serious bug fixes will be accepted on the 5.4-7.1 version

## Inspiration / Original

 - [Commando](https://github.com/nategood/commando)
 - [Commander](https://github.com/visionmedia/commander)
 - [Optimist](https://github.com/substack/node-optimist)

Released under MIT license.

## Change Log

### v0.5.0 (Multiple Breaking Changes)

 - Upgrade to PHP 5.4, support up to 7.1
 - remove PHP 5.3 from travis CI yaml / testing
 - add didShowHelp method
 - remove all die/exit statements, return valid exit codes instead
 - Port to new packagist under tolidano/commandox
 - Port to new GitHub repository under tolidano/commandox
 - Correct dedupeFlags bug (ensures each option/alias is seen exactly once)
 - Use title in help and error messages when defined for anonymous arguments
 - Constant refactoring
 - Omit `<argument>` for increment options help text
 - Fix non-empty getArgumentValues array
 - Allow subclassing of Command
 - Make tput OS-aware
 - Help now works with -h as well (conflicts with existing -h flags)

### v0.2.9

 - PR #63 FEATURE incremental flags
 - PR #60 MINOR getDescription method

### v0.2.8

 - Bug fix for #34

### v0.2.7

 - `getOptions` added (along with some better documentation)

### v0.2.6

 - Adds support for "needs" to define dependencies between options (thanks @enygma) [PR #31](https://github.com/nategood/commando/pull/31)
 - Fixes issue with long-argument-names [Issue #30](https://github.com/nategood/commando/issues/30)

### v0.2.5

 - Fixed up default values for boolean options, automatically default boolean options to false (unlikely, but potentially breaking change) [PR #19](https://github.com/nategood/commando/pull/19)

### v0.2.4

 - Added ability to define default values for options

### v0.2.3

 - Improved Help Formatting [PR #12](https://github.com/nategood/commando/pull/12)

### v0.2.2

 - Bug fix for printing double help [PR #10](https://github.com/nategood/commando/pull/10)

### v0.2.1

 - Adds support for requiring options to be valid file paths or globs
 - Returns a fully qualified file path name (e.g. converts relative paths)
 - Returns an array of file paths in the case of globbing
 - See the file.php example in the examples directory

### v0.2.0

The primary goal of this update was to better delineate between flag options and argument options.  In Commando, flags are options that we define that require a name when they are being specified on the command line.  Arguments are options that are not named in this way.  In the example below, '-f' and '--long' are described as "flags" type options in Commando terms with the values 'value1' and 'value2' respectively, whereas value3, value4, and value5 are described as "argument" type options.

```
php command.php -f value1 --long value2 value3 value4 value5
```

 - Added Command::getArguments() to return an array of `Option` that are of the "argument" type (see argumentsVsFlags.php example)
 - Added Command::getFlags() to return an array of `Option` that are of the "flag" type  (see argumentsVsFlags.php example)
 - Added Command::getArgumentValues() to return an array of all the values for "arguments"
 - Added Command::getFlagValues() to return an array of all values for "flags"
 - Command now implements Iterator interface and will iterator over all options, starting with arguments and continuing with flags in alphabetical order
 - Can now define options with Command::flag($name) and Command::argument(), in addition to Command::option($name)
 - Added ability to add a "title" to refer to arguments by, making the help docs a little cleaner (run help.php example)
 - Cleaned up the generated help docs
 - Bug fix for additional colorized red line when an error is displayed

### v0.1.4
 - Bug fix for options values with multiple words

### v0.1.3
 - Beep support added to Terminal
 - Commando::beepOnError() added

### v0.1.2
 - Terminal updated to use tput correctly
