<?php defined('SYSPATH') or die('No direct script access.');

abstract class Sprig_Field {

	public $empty = FALSE;

	public $primary = FALSE;

	public $unique = FALSE;

	public $null = FALSE;

	public $editable = TRUE;

	public $default = '';

	public $choices;

	public $column;

	public $label;

	public $description = '';

	public $filters = array();

	public $rules = array();

	public $callbacks = array();

	protected $value;

	public function __construct(array $options = NULL)
	{
		if ( ! empty($options))
		{
			$props = get_object_vars($this);

			unset($props['value']);

			$options = array_intersect_key($options, $props);

			foreach ($options as $key => $value)
			{
				$this->$key = $value;
			}
		}

		if ($this->default !== NULL)
		{
			$this->set($this->default);
		}
	}

	public function __clone()
	{
		if ($this->default !== NULL)
		{
			// Set the default value
			$this->set($this->default);
		}
		else
		{
			// Set an empty value
			$this->set(NULL);
		}
	}

	public function get()
	{
		return $this->value;
	}

	public function set($value)
	{
		if ($this->null AND empty($value))
		{
			// Empty values are converted to NULLs
			$value = NULL;
		}

		if ($this->value === $value)
		{
			return FALSE;
		}

		$this->value = $value;

		return TRUE;
	}

	public function raw()
	{
		return $this->value;
	}

	public function verbose()
	{
		return (string) $this->raw();
	}

	public function input($name, array $attr = NULL)
	{
		if (is_array($this->choices))
		{
			return Form::select($name, $this->choices, $this->value, $attr);
		}
		else
		{
			return Form::input($name, $this->verbose(), $attr);
		}
	}

	public function label($name, array $attr = NULL)
	{
		return Form::label($name, $this->label, $attr);
	}

} // End Sprig_Field