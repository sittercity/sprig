<?php defined('SYSPATH') or die('No direct script access.');

class Sprig_Field_ForeignKey extends Sprig_Field_Integer {

	public $null = TRUE;

	public function __construct(array $options = NULL)
	{
		$model = Sprig::factory($options['model']);

		// Set the choices for this field
		$this->choices = $model->select_list();

		parent::__construct($options);
	}

	public function set($value)
	{
		if (is_object($value))
		{
			$value = $value->id;
		}

		return parent::set($value);
	}

	public function input($name, array $attr = NULL)
	{
		return form::select($name, $this->choices, $this->value);
	}

} // End Sprig_Field_ForeignKey