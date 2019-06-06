<?php declare(strict_types = 1);
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
            $name = static::class;
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
    public function __get(string $name)
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
    private static function getConstants(): array
    {
        if (self::$constCacheArray == null) {
            self::$constCacheArray = [];
        }
        if (!array_key_exists(static::class, self::$constCacheArray)) {
            $reflect = new \ReflectionClass(static::class);
            self::$constCacheArray[static::class] = $reflect->getConstants();
        }
        return self::$constCacheArray[static::class];
    }

    /**
     * Determine if there is a value among the constants
     *
     * @param mixed $value
     * @return boolean
     */
    public static function isValidValue(/* mixed */ $value): bool
    {
        $values = array_values(self::getConstants());
        return in_array($value, $values);
    }
}
