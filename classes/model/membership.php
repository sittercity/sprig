<?php defined('SYSPATH') or die('No direct script access.');

class Model_Membership extends Sprig {

	protected function _init()
	{
		$this->_fields += array(
			'student' => new Sprig_Field_BelongsTo(array(
				'primary' => TRUE,
			)),
			'club' => new Sprig_Field_BelongsTo(array(
				'primary' => TRUE,
			)),
			'joined_on' => new Sprig_Field_Timestamp(array(
				'auto_now_create' => TRUE,
				'format' => 'F jS, Y',
			)),
		);
	}

} // End Membership