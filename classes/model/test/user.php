<?php defined('SYSPATH') or die('No direct script access.');

class Model_Test_User extends Sprig {
	
	protected function _init()
	{
		$this->_fields += array(
			'id'          => new Sprig_Field_Auto,
			'tags'        => new Sprig_Field_ManyToMany(array('model'=>'Test_Tag')),
			'name'        => new Sprig_Field_HasOne(array('model'=>'Test_Name')),
			'year'        => new Sprig_Field_Integer,
			'title'       => new Sprig_Field_Char(array('max_length' => 20, 'default' => 'Sir')),
			'joined'      => new Sprig_Field_Timestamp(array('auto_now_create' => TRUE)),
			'last_online' => new Sprig_Field_Timestamp(array('auto_now_update' => TRUE)),
			'last_breathed' => new Sprig_Field_NativeTimestamp(array('auto_now_update' => TRUE)),
		);
	}
}
