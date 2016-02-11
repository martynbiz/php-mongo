<?php

namespace MartynBiz\Mongo\Exception;

/**
 * White list is required on all models to protected against mass assingment attacks
 */
class WhitelistEmpty extends \Exception
{

}
