<?php defined('SYSPATH') or die('No direct script access.');

class Sprig_Field_HasMany extends Sprig_Field_ForeignKey {

	public $default = array();

	public function set($value)
	{
		return parent::set((array) $value);
	}

	public function input($name, array $attr = NULL)
	{
		$inputs = array();

		// foreach ($this->value)
		// {
		// 	$inputs[] = form::checkbox("{$name}[]", $value, in_array($value, $this->value));
		// }

		return $inputs;
	}

} // End Sprig_Field_ManyToMany
