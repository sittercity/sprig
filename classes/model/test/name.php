<?php defined('SYSPATH') or die('No direct script access.');

class Model_Test_Name extends Sprig {
	
	protected function _init()
	{
		$this->_fields += array(
			'test_user_id' => new Sprig_Field_Integer,
			'user' => new Sprig_Field_BelongsTo(array('model'=>'Test_User')),
			'name' => new Sprig_Field_Char(array('max_length' => 20)),
		);
	}
}