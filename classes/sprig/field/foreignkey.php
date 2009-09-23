<?php defined('SYSPATH') or die('No direct script access.');

abstract class Sprig_Field_ForeignKey extends Sprig_Field {

	public $null = TRUE;

	public $model;

	public function set($value)
	{
		if ($value instanceof Sprig)
		{
			// Get the primary key value instead of an object
			$value = $value->{$value->pk()};
		}

		return parent::set($value);
	}

} // End Sprig_Field_ForeignKey