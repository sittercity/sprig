<?php defined('SYSPATH') or die('No direct script access.');

class Sprig_Field_BelongsTo extends Sprig_Field_ForeignKey {

	public $in_db = TRUE;

	/**
	 * Get the scalar default value of this Field, if applicable
	 *
	 * @return mixed|Sprig_Void Returns a Sprig_Void instance if a default value
	 *                          is not applicable to this Field.
	 *                          Otherwise, returns the default value.
	 */
	public function default_value()
	{
		// Set the default value for any field that is stored in the database
		return $this->value($this->default);
	}

	public function input($name, $value, array $attr = NULL)
	{
		$model = Sprig::factory($this->model);

		$choices = $model->select_list($model->pk());

		if ($this->empty)
		{
			Arr::unshift($choices, '', '-- '.__('None'));
		}

		return Form::select($name, $choices, $this->verbose($value), $attr);
	}

	/**
	 * Extracts the related object representing the value of a foreign key Field
	 *
	 * @param mixed $value The current scalar value of this Field
	 *
	 * @return Sprig|array
	 */
	public function get_related($value)
	{
		$model = Sprig::factory($this->model);

		if (isset($this->primary_key) AND $this->primary_key)
		{
			$pk = $this->primary_key;
		}
		else
		{
			$pk = $model->pk();
		}

		return $model->values(array($pk => $value));
	}

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
		// For some Sprig Fields, setting the related values is not necessary
		// Pass
		return NULL;
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
			if (isset($this->foreign_key) AND $this->foreign_key)
			{
				$fk = $this->foreign_key;
			}
			else
			{
				$fk = Sprig::factory($this->model)->fk();
			}

			$this->column = $fk;
		}
		return $this->column;
	}

} // End Sprig_Field_BelongsTo
