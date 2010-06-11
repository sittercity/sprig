<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Basic Sprig_Field implementation.
 *
 * @package	   Sprig
 * @author	   Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license	   MIT
 */
abstract class Sprig_Field_Core {

	/**
	 * @var bool Allow `empty()` values to be used. Default is `FALSE`.
	 */
	public $empty = FALSE;

	/**
	 * @var bool A primary key field. Multiple primary keys (composite key) can be specified. Default is `FALSE`.
	 */
	public $primary = FALSE;

	/**
	 * @var bool This field must have a unique value within the model table. Default is `FALSE`.
	 */
	public $unique = FALSE;

	/**
	 * @var bool Convert all `empty()` values to `NULL`. Default is `FALSE`.
	 */
	public $null = FALSE;

	/**
	 * @var bool Show the field in forms. Default is `TRUE`.
	 */
	public $editable = TRUE;

	/**
	 * @var string Default value for this field. Default is `''` (an empty string).
	 */
	public $default = '';

	/**
	 * @var array Limit the value of this field to an array of choices. This will change the form input into a select list. No default value.
	 */
	public $choices;

	/**
	 * @var string Database column name for this field. Default will be the same as the field name,
	 * except for foreign keys, which will use the field name with `_id` appended.
	 * In the case of HasMany fields, this value is the column name that contains the
	 * foreign key value.
	 */
	public $column;

	/**
	 * @var string Human readable label. Default will be the field name converted with `Inflector::humanize()`.
	 */
	public $label;

	/**
	 * @var string Description of the field. Default is `''` (an empty string).
	 */
	public $description = '';

	 /**
	 * @var array {@link HTML} html attribute for the field.
	 */
	public $attributes = NULL;

	/**
	 * @var bool The column is present in the database table. Default: TRUE
	 */
	public $in_db = TRUE;

	/**
	 * @var array {@link Validate} filters for this field.
	 */
	public $filters = array();

	/**
	 * @var array {@link Validate} rules for this field.
	 */
	public $rules = array();

	/**
	 * @var array {@link Validate} callbacks for this field.
	 */
	public $callbacks = array();

	/**
	 * @var  object  {@link Sprig} model parent
	 */
	public $object;

	// Initialization status
	protected $_init = FALSE;

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

	/**
	 * Initialize the Sprig Field. This method will only be called once by
	 * Sprig.
	 *
	 * @param Sprig  $object     The parent object which contains $this Field
	 * @param string $field_name The name of $this Field on the parent object
	 *
	 * @return null
	 */
	public function init(Sprig $object, $field_name)
	{
		if ($this->_init)
		{
			throw new Sprig_Exception(
				':field has already been initialized, and cannot be shared.',
				array(':field' => $field_name)
			);
		}

		// Initialization has been started
		$this->_init = TRUE;

		$this->object = $object;

		if ( ! $this->column )
		{
			// Initialize the $this->column property
			// Create the key based on the field name
			$this->column($field_name);
		}

		$this->label = Inflector::humanize($field_name);

		if ($this->null)
		{
			// Fields that allow NULL values must accept empty values
			$this->empty = TRUE;
		}

		if ($this->editable)
		{
			$this->editable_defaults();
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
		$value = $this->value($value);

		return (string) isset($this->choices[$value]) ? $this->choices[$value] : $value;
	}

	public function input($name, $value, array $attr = NULL)
	{
		if (is_array($this->choices))
		{
			return Form::select($name, $this->choices, $this->value($value), $attr);
		}
		else
		{
			return Form::input($name, $this->verbose($value), $attr);
		}
	}

	public function label($name, array $attr = NULL)
	{
		return Form::label($name, UTF8::ucwords($this->label), $attr);
	}
	
	public function _database_wrap($value)
	{
		return $value;
	}
	
	public function _database_unwrap($value)
	{
		return $value;
	}

	/**
	 * Get and optionally set the public $this->column property, based on
	 * $field_name.  Provided as a method, so that the behavior can be
	 * easily overridden.
	 *
	 * @param string $field_name
	 *
	 * @return string
	 */
	protected function column($field_name = null)
	{
		if (null !== $field_name)
		{
			$this->column = $field_name;
		}
		return $this->column;
	}

	protected function editable_defaults()
	{
		if ( ! $this->empty AND ! isset($this->rules['not_empty']) )
		{
			// This field must not be empty
			$this->rules['not_empty'] = NULL;
		}

		if ($this->unique)
		{
			// Field must be a unique value
			$this->callbacks[] = array($this->object, '_unique_field');
		}

		if ($this->choices AND ! isset($this->rules['in_array']))
		{
			// Field must be one of the available choices
			$this->rules['in_array'] = array(array_keys($this->choices));
		}

		if ( ! empty($this->min_length) )
		{
			$this->rules['min_length'] = array($this->min_length);
		}

		if ( ! empty($this->max_length) )
		{
			$this->rules['max_length'] = array($this->max_length);
		}
	}

} // End Sprig_Field