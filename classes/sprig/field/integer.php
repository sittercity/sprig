<?php defined('SYSPATH') or die('No direct script access.');

class Sprig_Field_Integer extends Sprig_Field {

	public $type = 'int';

	public $default = 0;

	public $min_value;

	public $max_value;

	public function set($value)
	{
		return parent::set((int) $value);
	}

} // End Sprig_Field_Integer