<?php defined('SYSPATH') or die('No direct script access.');

class Model_Student extends Sprig {

	protected function _init()
	{
		$this->_fields += array(
			'id' => new Sprig_Field_Auto,
			'name' => new Sprig_Field_Char,
			'registered' => new Sprig_Field_Timestamp(array(
				'editable' => FALSE,
				'auto_now_create' => TRUE,
				'format' => 'Y-m-d',
			)),
			'clubs' => new Sprig_Field_ManyToMany(array(
				'through' => 'memberships',
			)),
			'memberships' => new Sprig_Field_HasMany,
			'classes' => new Sprig_Field_ManyToMany,
			'car' => new Sprig_Field_HasOne,
		);
	}

} // End Student