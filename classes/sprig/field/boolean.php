<?php defined('SYSPATH') or die('No direct script access.');

class Sprig_Field_Boolean extends Sprig_Field {

	public $text = 'Yes';

	public $empty = TRUE;

	public $default = FALSE;

	public $filters = array('filter_var' => array(FILTER_VALIDATE_BOOLEAN));

	public function set($value)
	{
		return parent::set((bool) $value);
	}

	public function input($name, array $attr = NULL)
	{
		return '<label>'.form::checkbox($name, 1, $this->value, $attr).' '.$this->text.'</label>';
	}

} // End Sprig_Field_Boolean