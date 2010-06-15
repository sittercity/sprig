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
	 * Get the scalar default value of this Field, if applicable
	 *
	 * @return mixed|Sprig_Void Returns a Sprig_Void instance if a default value
	 *                          is not applicable to this Field.
	 *                          Otherwise, returns the default value.
	 */
	public function default_value()
	{
		// For most ForeignKey Fields, let's assume that the parent $object does
		// not possess a default value of the Field - return Void
		return new Sprig_Void;
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
		parent::init($object, $field_name);

		if ( ! $this->model )
		{
			// Initialize the $this->model property
			$this->model($field_name);
		}
	}

	/**
	 * Provides the original values for a Sprig Field if they are not set.  This
	 * is only applicable for certain Sprig Fields.
	 *
	 * @return array|null Returns an array of the original values, or NULL if
	 *                    not applicable
	 */
	public function set_original()
	{
		// For most Sprig Fields, setting the original values is not necessary
		return NULL;
	}

	/**
	 * Extracts the related object representing the value of a foreign key Field
	 *
	 * @param mixed $value The current scalar value of this Field
	 *
	 * @return Sprig|array
	 */
	abstract public function get_related($value);

	/**
	 * Provides the related values for a Sprig Field if they are not set.  This
	 * is only applicable for certain Sprig Fields.
	 *
	 * @param mixed $value The value or values to set
	 *
	 * @return array|null Returns an array of the related values, or NULL if
	 *                    not applicable
	 *
	 * @throws Sprig_Exception Exception thrown if attempting to replace a
	 *                         Sprig relationship that does not support being
	 *                         overridden.
	 */
	public function set_related($value)
	{
		throw new Sprig_Exception(
			'Cannot change relationship of :model->:field using __set()',
			array(':model' => $this->object->model(), ':field' => $this->label)
		);
	}

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