<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Basic Sprig_Field implementation.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
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

	public $in_db = TRUE;

	public $filters = array();

	public $rules = array();

	public $callbacks = array();

	public function __construct(array $options = NULL)
	{
		if ( ! empty($options))
		{
			$options = array_intersect_key($options, get_object_vars($this));

			foreach ($options as $key => $value)
			{
				$this->$key = $value;
			}
		}
	}

	public function value($value)
	{
		if ($this->null AND empty($value))
		{
			// Empty values are converted to NULLs
			$value = NULL;
		}

		return $value;
	}

	public function verbose($value)
	{
		return (string) $this->value($value);
	}

	public function input($name, $value, array $attr = NULL)
	{
		// Make the value verbose
		$value = $this->verbose($value);

		if (is_array($this->choices))
		{
			return Form::select($name, $this->choices, $value, $attr);
		}
		else
		{
			return Form::input($name, $value, $attr);
		}
	}

	public function label($name, array $attr = NULL)
	{
		return Form::label($name, UTF8::ucwords($this->label), $attr);
	}

} // End Sprig_Field
