<?php defined('SYSPATH') or die('No direct script access.');

class Sprig_Field_Char extends Sprig_Field {

	public $min_length;

	public $max_length;

	public function set($value)
	{
		return parent::set((string) $value);
	}

} // End Sprig_Field_Char
