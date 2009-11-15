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

    /**
     * Allow `empty()` values to be used. Default is `FALSE`.
     * @var bool
     */
	public $empty = FALSE;

    /**
     * A primary key field. Multiple primary keys (composite key) can be specified. Default is `FALSE`.
     * @var bool
     */
	public $primary = FALSE;

    /**
     * This field must have a unique value within the model table. Default is `FALSE`.
     * @var bool
     */
	public $unique = FALSE;

    /**
     * Convert all `empty()` values to `NULL`. Default is `FALSE`.
     * @var bool
     */
	public $null = FALSE;

    /**
     * Show the field in forms. Default is `TRUE`.
     * @var bool
     */
	public $editable = TRUE;

    /**
     * Default value for this field. Default is `''` (an empty string).
     * @var string
     */
	public $default = '';

    /**
     * Limit the value of this field to an array of choices. This will change the form input into a select list. No default value.
     * @var array
     */
	public $choices;

    /**
     * Database column name for this field. Default will be the same as the field name,
     * except for foreign keys, which will use the field name with `_id` appended.
     * In the case of HasMany fields, this value is the column name that contains the
     * foreign key value.
     * @var string
     */
	public $column;

    /**
     * Human readable label. Default will be the field name converted with `Inflector::humanize()`.
     * @var string
     */
	public $label;

    /**
     * Description of the field. Default is `''` (an empty string).
     * @var string
     */
	public $description = '';

    /**
     * If true, the column is present in the database table. Default: TRUE
     * @var bool
     */
	public $in_db = TRUE;

    /**
     * {@link Kohana_Validate} filters for this field.
     * @var array
     */
	public $filters = array();

    /**
     * {@link Kohana_Validate} rules for this field.
     * @var array
     */
	public $rules = array();

    /**
     * {@link Kohana_Validate} callbacks for this field.
     * @var array
     */
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
