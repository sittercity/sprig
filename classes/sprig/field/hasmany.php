<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig "has many" relationship field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
class Sprig_Field_HasMany extends Sprig_Field_ForeignKey {

	public $empty = TRUE;

	public $default = array();

	public $editable = FALSE;

	public function value($value)
	{
		if (empty($value) AND $this->empty)
		{
			return array();
		}
		elseif (is_object($value))
		{
			$model = Sprig::factory($this->model);

			// Assume this is a Database_Result object
			$value = $value->as_array(NULL, $model->pk());
		}
		else
		{
			// Value must always be an array
			$value = (array) $value;
		}

		if ($value)
		{
			// Combine the value to make a mirrored array
			$value = array_combine($value, $value);

			foreach ($value as $id)
			{
				// Convert the value to the proper type
				$value[$id] = parent::value($id);
			}
		}

		return $value;
	}

	public function verbose($value)
	{
		return implode(', ', $this->value($value));
	}

	public function input($name, $value, array $attr = NULL)
	{
		$model = Sprig::factory($this->model);

		// All available options
		$options = $model->select_list($model->pk());

		// Convert the selected options
		$value = $this->value($value);

		$inputs = array();
		foreach ($options as $id => $label)
		{
			$inputs[] = '<label>'.Form::checkbox("{$name}[]", $id, isset($value[$id])).' '.$label.'</label>';
		}

		// Hidden input is added to force $_POST to contain a value for
		// this field, even when nothing is selected.

		return Form::hidden($name, '').implode('<br/>', $inputs);
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
		$query = $this->related_query($model, $value);
		if ($query instanceof Database_Query_Builder_Select)
		{
			return $model->load($query, NULL);
		}
		return new Database_Result_Cached(array(), '');
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
		foreach ($value as $key => $val)
		{
			if ( ! $val instanceof Sprig )
			{
				$model = Sprig::factory($field->model);
				$pk    = $model->pk();

				if ( ! is_array($val) )
				{
					// Assume the value is a primary key
					$val = array($pk => $val);
				}

				if (isset($val[$pk]))
				{
					// Load the record so that changed values can be determined
					$model->values(array($pk => $val[$pk]))->load();
				}

				$value[$key] = $model->values($val);
			}
		}

		return $value;
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
			$this->model = Inflector::singular($field_name);
		}
		return $this->model;
	}

	/**
	 * Produces a Select Query for retrieving this HasMany ForeignKey Field
	 * relationship based on the current scalar $value.
	 *
	 * @param Sprig $model A prototype instance of $this->model
	 * @param mixed $value The current scalar value of this Field
	 *
	 * @return Database_Query_Builder_Select|null
	 */
	protected function related_query(Sprig $model, $value)
	{
		if ($value instanceof Sprig_Void)
		{
			if (isset($this->foreign_key) AND $this->foreign_key)
			{
				$fk = $this->foreign_key;
			}
			else
			{
				$fk = $model->fk();
			}

			$parent = $this->object;
			$pk = $parent->pk();
			$query = DB::select()
				->where(
					$fk,
					'=',
					$parent->field($pk)->_database_wrap($parent->{$pk}));
		}
		else
		{
			$query = DB::select()
				->where(
					$model->pk(),
					'=',
					$this->_database_wrap($value));
		}
		return $query;
	}

} // End Sprig_Field_ManyToMany
