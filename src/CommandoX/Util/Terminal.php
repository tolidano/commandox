<?php declare(strict_types = 1);
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
    /**
     * Width of current terminal window
     * On Linux/Mac flavor systems, will use tput.  Falls back to a
     * default value of $default.  On Windows, will always fall back
     * to default.
     *
     * @param int $default
     * @return int
     */
    public static function getWidth($default = 80): int
    {
        return self::tput($default, 'cols');
    }

    /**
     * Height of current terminal window
     * @see getWidth
     *
     * @param int $default
     * @return int
     */
    public static function getHeight($default = 32): int
    {
        return self::tput($default, 'lines');
    }

    /**
     * Make the terminal beep
     *
     * @return void
     */
    public static function beep(): void
    {
        print('\x7');
    }

    /**
     * Sadly if you attempt to redirect stderr, e.g. "tput cols 2>/dev/null"
     * tput does not return the expected values.  As a result, to prevent tput
     * from writing to stderr, we first check the exit code and call it again
     * to get the actual value.
     *
     * @param int $default
     * @param string $param
     * @return int
     */
    private static function tput($default, $param = 'cols'): int
    {
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
     * @param string $text
     * @param int $left_margin
     * @param int $right_margin
     * @param ?int $width attempts to use current terminal width by default
     * @return string
     */
    public static function wrap(string $text, int $left_margin = 0, int $right_margin = 0,
        ?int $width = null): string
    {
        if (empty($width)) {
            $width = self::getWidth();
        }
        $width = $width - abs($left_margin) - abs($right_margin);
        $margin = str_repeat(' ', $left_margin);
        return $margin . wordwrap($text, $width, PHP_EOL . $margin);
    }

    /**
     * Header for various text output
     *
     * @param string $text
     * @param int $width defaults to terminal width
     * @return string
     */
    public static function header(string $text, ?int $width = null): string
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
    public static function pad(string $text, int $width, string $pad = ' ',
        int $mode = STR_PAD_RIGHT): string
    {
        $width = strlen($text) - mb_strlen($text, 'UTF-8') + $width;
        return str_pad($text, $width, $pad, $mode);
    }
}
