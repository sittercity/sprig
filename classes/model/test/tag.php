<?php defined('SYSPATH') or die('No direct script access.');

class Model_Test_Tag extends Sprig {
	
	protected function _init()
	{
		$this->_fields += array(
			'id'    => new Sprig_Field_Auto,
			'users' => new Sprig_Field_ManyToMany(array('model'=>'Test_User')),
			'name'  => new Sprig_Field_Char(array('max_length' => 20)),
		);
	}
}
