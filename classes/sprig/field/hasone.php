<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig "has one" relationship field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
class Sprig_Field_HasOne extends Sprig_Field_ForeignKey {

	public $editable = FALSE;

	public function input($name, $value, array $attr = NULL)
	{
		$model = Sprig::factory($this->model);

		$choices = $model->select_list($model->pk());

		return Form::select($name, $choices, $this->verbose($value));
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
		$parent = $this->object;
		$pk = $parent->pk();
		return $model->values(array($parent->model() => $parent->{$pk}));
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
			$this->column = $this->object->fk();
		}
		return $this->column;
	}

} // End Sprig_Field_HasOne
