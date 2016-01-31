<?php

use MartynBiz\Mongo\Utils;

class UtilsTest extends PHPUnit_Framework_TestCase
{
	/**
	 * @dataProvider getSnakeCaseToCamelCaseData
	 */
	public function testSnakeCaseToCamelCase($str, $expected)
    {
        $result = Utils::snakeCaseToCamelCase($str);

		$this->assertEquals($expected, $result);
    }

	public function getSnakeCaseToCamelCaseData()
	{
		return array(
			array('users', 'Users'),
			array('first_name', 'FirstName'),
		);
	}

	/**
	 * @dataProvider getCamelCaseToSnakeCaseData
	 */
	public function testCamelCaseToSnakeCase($str, $expected)
    {
        $result = Utils::camelCaseToSnakeCase($str);

		$this->assertEquals($expected, $result);
    }

	public function getCamelCaseToSnakeCaseData()
	{
		return array(
			array('Users', 'users'),
			array('FirstName', 'first_name'),
		);
	}
}
