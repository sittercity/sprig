<?php defined('SYSPATH') or die('No direct script access.');

abstract class Sprig_Model_User extends Sprig {

	protected $_table = 'users';

	public function __set($field, $value)
	{
		if ($field === 'password')
		{
			$value = sha1($value);
		}

		return parent::__set($field, $value);
	}

	protected function _init()
	{
		$this->_fields += array(
			'id' => new Sprig_Field_Auto,
			'username' => new Sprig_Field_Char(array(
				'empty'  => FALSE,
				'unique' => TRUE,
				'rules'  => array(
					'regex' => array('/^[\pL_.-]+$/ui')
				),
			)),
			'password' => new Sprig_Field_Char(array(
				'empty' => FALSE,
			)),
		);
	}

} // End User
