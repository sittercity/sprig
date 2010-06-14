<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig foreign key field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
abstract class Sprig_Field_ForeignKey extends Sprig_Field_Char {

	public $null = TRUE;

	public $in_db = FALSE;

	public $model;

	public $foreign_key = NULL;

	public $primary_key = NULL;

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
		parent::init($object, $field_name);

		if ( ! $this->model )
		{
			// Initialize the $this->model property
			$this->model($field_name);
		}
	}

	/**
	 * Extracts the related object representing the value of a foreign key Field
	 *
	 * @param mixed $value The current scalar value of this Field
	 *
	 * @return Sprig|array
	 */
	abstract public function related($value);

	public function value($value)
	{
		if (is_object($value))
		{
			// Assume this is a Sprig object
			$value = $value->{$value->pk()};
		}

		return parent::value($value);
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
		// This field is probably a Many and does not need a column
		// So do not set it, but we'll return what we've got

		return $this->column;
	}

	/**
	 * Get and optionally set the public $this->model property, based on
	 * $field_name.  Provided as a method, so that the behavior can be
	 * easily overridden.
	 *
	 * @param string $field_name
	 *
	 * @return string
	 */
	protected function model($field_name = null)
	{
		if (null !== $field_name)
		{
			$this->model = $field_name;
		}
		return $this->model;
	}

} // End Sprig_Field_ForeignKey