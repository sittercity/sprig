<?php defined('SYSPATH') or die('No direct script access.');

class Sprig_Field_Timestamp extends Sprig_Field_Integer {

	public $auto_now_create = FALSE;

	public $auto_now_update = FALSE;

	public $default = NULL;

	public $format = 'Y-m-d G:i:s A';

	public function set($value)
	{
		if (is_string($value) AND ! ctype_digit($value))
		{
			$value = strtotime($value);
		}

		return parent::set($value);
	}

	public function verbose()
	{
		if ($this->value)
		{
			return date($this->format, $this->value);
		}
		else
		{
			return $this->value;
		}
	}

} // End Sprig_Field_Timestamp
