<?php defined('SYSPATH') or die('No direct script access.');

class Sprig_Field_ManyToMany extends Sprig_Field {

	public $default = array();

	public $column = '';

	public $model;

	public $through;

	public $rules = array('is_array' => NULL);

	public function set($value)
	{
		return parent::set((array) $value);
	}

	public function input($name, array $attr = NULL)
	{
		$inputs = array();

		foreach ($this->choices as $value => $label)
		{
			$inputs[] = form::checkbox("{$name}[]", $value, in_array($value, $this->value)).' '.$label;
		}

		return $inputs;
	}

} // End Sprig_Field_ManyToMany
