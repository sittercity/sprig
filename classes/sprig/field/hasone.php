<?php defined('SYSPATH') or die('No direct script access.');

class Sprig_Field_HasOne extends Sprig_Field_ForeignKey {

	public function set($value)
	{
		if ($value instanceof Sprig)
		{
			$value = $value->{$value->pk()};
		}

		return parent::set($value);
	}

	public function input($name, array $attr = NULL)
	{
		return form::select($name, $this->choices, $this->value);
	}

} // End Sprig_Field_HasOne
