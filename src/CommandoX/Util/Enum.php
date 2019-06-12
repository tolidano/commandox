<?php
/**
 * @author Shawn Tolidano <shawn@tolidano.com>
 */
namespace CommandoX\Util;

/**
 * Enum definition
 */
abstract class Enum
{
    private static $constCacheArray = null;

    private $value = null;

    /**
     * Construct with value
     *
     * @param mixed $value
     */
    public function __construct(/* mixed */ $value)
    {
        if (!self::isValidValue($value)) {
            $name = get_called_class();
            throw new \Exception("Invalid value $value for enum $name");
        }
        $this->value = $value;
    }

    /**
     * Get value with magic
     *
     * @param string $name
     * @return void
     */
    public function __get($name)
    {
        if ($name === 'value') {
            return $this->value;
        }
        throw new \Exception('Unable to retrieve value for ' . $name);
    }

    /**
     * Retrieve an array of all the constants defined
     *
     * @return array
     */
    private static function getConstants()
    {
        if (self::$constCacheArray == null) {
            self::$constCacheArray = [];
        }
        $calledClass = get_called_class();
        if (!array_key_exists($calledClass, self::$constCacheArray)) {
            $reflect = new \ReflectionClass($calledClass);
            self::$constCacheArray[$calledClass] = $reflect->getConstants();
        }
        return self::$constCacheArray[$calledClass];
    }

    /**
     * Determine if there is a value among the constants
     *
     * @param mixed $value
     * @return boolean
     */
    public static function isValidValue(/* mixed */ $value)
    {
        $values = array_values(self::getConstants());
        return in_array($value, $values);
    }
}
