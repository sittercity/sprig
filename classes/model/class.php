<?php defined('SYSPATH') or die('No direct script access.');

class Model_Class extends Sprig {

	protected function _init()
	{
		$this->_fields += array(
			'id' => new Sprig_Field_Auto,
			'name' => new Sprig_Field_Char,
			'level' => new Sprig_Field_Enum(array(
				'choices' => array(
					'101' => '101',
					'201' => '201',
					'301' => '301',
					'401' => '401',
				),
			)),
			'students' => new Sprig_Field_ManyToMany,
		);
	}

} // End Class