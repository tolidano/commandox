<?php
/**
 * @author Shawn Tolidano <shawn@tolidano.com>
 * @author Nate Good <me@nategood.com>
 */
namespace CommandoX\Util;

/**
 * Utilities for manipulating the terminal
 */
class Terminal
{
    private static $osType = null;

    /**
     * Width of current terminal window
     * On Linux/Mac flavor systems, will use tput.  Falls back to a
     * default value of $default.  On Windows, will always fall back
     * to default.
     *
     * @param  int $default
     * @return int
     */
    public static function getWidth($default = 80)
    {
        return self::tput($default, 'cols');
    }

    /**
     * Height of current terminal window
     *
     * @see getWidth
     *
     * @param  int $default
     * @return int
     */
    public static function getHeight($default = 32)
    {
        return self::tput($default, 'lines');
    }

    /**
     * Make the terminal beep
     *
     * @return void
     */
    public static function beep()
    {
        print('\x7');
    }

    /**
     * Override the OS
     *
     * @param string $osType
     *
     * @return void
     */
    public static function setOsType(string $osType)
    {
        self::$osType = $osType;
    }

    /**
     * Sadly if you attempt to redirect stderr, e.g. "tput cols 2>/dev/null"
     * tput does not return the expected values.  As a result, to prevent tput
     * from writing to stderr, we first check the exit code and call it again
     * to get the actual value.
     * If we detect windows or cannot detect tpu, return the default
     *
     * @param  int    $default
     * @param  string $param
     * @return int
     */
    private static function tput($default, $param = 'cols')
    {
        $phpOs = strtolower(substr(PHP_OS, 0, 3));
        if (!self::$osType) {
            self::$osType = getenv('OS');
        }
        $envOs = self::$osType;
        $whichTput = shell_exec('which tput');
        if ($phpOs === 'win' ||
            ($envOs && strpos(strtolower($envOs), 'windows') !== false) ||
            empty(trim($whichTput))) {
            return $default;
        }
        $test = exec('tput ' . $param . ' 2>/dev/null');
        if (empty($test)) {
            return $default;
        }
        $result = intval(exec('tput ' . $param));
        return empty($result) ? $default : $result;
    }

    /**
     * Wrap text for printing
     *
     * @param  string $text
     * @param  int    $leftMargin
     * @param  int    $rightMargin
     * @param  ?int   $width        attempts to use current terminal width by default
     * @return string
     */
    public static function wrap($text, $leftMargin = 0, $rightMargin = 0, $width = null)
    {
        if (empty($width)) {
            $width = self::getWidth();
        }
        $width = $width - abs($leftMargin) - abs($rightMargin);
        $margin = str_repeat(' ', $leftMargin);
        return $margin . wordwrap($text, $width, PHP_EOL . $margin);
    }

    /**
     * Header for various text output
     *
     * @param  string $text
     * @param  int    $width defaults to terminal width
     * @return string
     */
    public static function header($text, $width = null)
    {
        if (empty($width)) {
            $width = self::getWidth();
        }
        return self::pad($text, $width);
    }

    /**
     * A UTF-8 compatible string pad
     *
     * @param string $text
     * @param int    $width
     * @param string $pad
     * @param int    $mode
     *
     * @return string
     */
    public static function pad($text, $width, $pad = ' ', $mode = STR_PAD_RIGHT)
    {
        $width = strlen($text) - mb_strlen($text, 'UTF-8') + $width;
        return str_pad($text, $width, $pad, $mode);
    }
}
