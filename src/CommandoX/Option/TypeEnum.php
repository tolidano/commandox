<?php
/**
 * TypeEnum Definition
 */
namespace CommandoX\Option;

use \CommandoX\Util\Enum;

/**
 * Class TypeEnum
 *
 * Extend dynamic Enum
 */
class TypeEnum extends Enum
{
    const SHORT = 1;
    const LONG = 2;
    const ARGUMENT = 4;

  /**
   * Convenience method
   *
   * Check if the instance value is LONG or SHORT
   *
   * @return bool
   */
    public function isNamed()
    {
        return static::isValueNamed($this->value);
    }

  /**
   * Check if the $value is one of LONG or SHORT
   *
   * @param mixed $value
   * @return bool
   */
    public static function isValueNamed(/* mixed */ $value)
    {
        return ($value === static::SHORT || $value === static::LONG);
    }
}
