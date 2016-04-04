<?php

namespace MartynBiz\Mongo\Traits;

/**
 * Can be re-used for anything that requires singleton pattern
 */
trait Singleton
{
    /**
     * @var Singleton The reference to *Singleton* instance of this class
     */
    private static $instances = array();

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function getInstance($name='default')
    {
        if (! isset(static::$instances[$name])) {
            static::$instances[$name] = new static();
        }

        return static::$instances[$name];
    }

    /**
     * Allows the instance to be swapped during tests
	 * @param Connection $instance New instance
     */
    public static function setInstance(self $instance, $name='default')
    {
        static::$instances[$name] = $instance;
    }

	/**
	 * As this is a singleton instance, we wanna be able to re-instatiate (e.g. during
     * tests as the same instance will be used for unit and integrated tests)
	 * @return void
	 */
	public function resetInstance($name=null)
	{
        if (is_null($name)) {
            static::$instances = array();
        } else {
            unset(static::$instances[$name]);
        }
	}

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {

    }
}
