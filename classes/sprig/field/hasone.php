<?php defined('SYSPATH') or die('No direct script access.');

class Sprig_Field_HasOne extends Sprig_Field_ForeignKey {

	public function __construct(array $options = NULL)
	{
		parent::__construct($options);

		if ($this->choices === NULL)
		{
			$this->choices = Sprig::factory($this->model)->select_list();
		}
	}

	public function set($value)
	{
		if (is_object($value) AND is_object($this->value))
		{
			if ($this->raw() === $value->{$value->pk()})
			{
				return FALSE;
			}
		}

		$this->value = $value;

		return TRUE;
	}

	public function raw()
	{
		return $this->value ? $this->value->{$this->value->pk()} : parent::raw();
	}

	public function input($name, array $attr = NULL)
	{
		return Form::select($name, $this->choices, $this->verbose());
	}

} // End Sprig_Field_HasOne
