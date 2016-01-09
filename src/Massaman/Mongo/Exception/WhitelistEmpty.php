<?php

namespace Massaman\Mongo\Exception;

/**
 * White list is required on all models to protected against mass assingment attacks
 */
class WhitelistEmpty extends \Exception
{

}
