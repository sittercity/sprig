<?php defined('SYSPATH') or die('No direct script access.');

class Sprig_Model_User extends Sprig {

	protected $_table = 'users';

	public function init()
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

		parent::init();
	}

} // End User