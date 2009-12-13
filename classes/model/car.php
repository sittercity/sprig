<?php defined('SYSPATH') or die('No direct script access.');

class Model_Car extends Sprig {

	protected function _init()
	{
		$this->_fields += array(
			'student' => new Sprig_Field_BelongsTo(array(
				'model'   => 'student',
				'primary' => TRUE,
			)),
			'license' => new Sprig_Field_Char(array(
				'unique' => TRUE,
			)),
		);
	}

} // End Car