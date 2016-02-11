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
    private static $instance;

    /**
     * Returns the *Singleton* instance of this class.
     *
     * @return Singleton The *Singleton* instance.
     */
    public static function getInstance()
    {
        if (null === static::$instance) {
            static::$instance = new static();
        }

        return static::$instance;
    }

    /**
     * Allows the instance to be swapped during tests
	 * @param Connection $instance New instance
     */
    public static function setInstance(self $instance)
    {
        static::$instance = $instance;
    }

	/**
	 * As this is a singleton instance, we wanna be able to re-instatiate (e.g. during
     * tests as the same instance will be used for unit and integrated tests)
	 * @return void
	 */
	public function resetInstance()
	{
		static::$instance = null;
	}

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {

    }
}
