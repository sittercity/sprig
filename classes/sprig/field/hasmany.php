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
		if (is_object($value))
		{
			$model = Sprig::factory($this->model);

			// Assume this is a Database_Result object
			$value = $value->as_array($model->pk(), $model->pk());
		}
		elseif (empty($value) AND $this->empty)
		{
			$value = array();
		}
		else
		{
			// Value must always be an array
			$value = (array) $value;

			// Combine the values to make a mirrored array
			$value = array_combine($value, $value);
		}

		foreach ($value as $id)
		{
			$value[$id] = parent::value($id);
		}

		return $value;
	}

	public function verbose($value)
	{
		return implode(', ', $value);
	}

	public function input($name, $value, array $attr = NULL)
	{
		$model = Sprig::factory($this->model);

		// All available options
		$options = $model->select_list($model->pk());

		$inputs = array();
		foreach ($options as $id => $label)
		{
			$inputs[] = '<label>'.Form::checkbox("{$name}[]", $id, isset($value[$id])).' '.$label.'</label>';
		}

		// Hidden input is added to force $_POST to contain a value for
		// this field, even when nothing is selected.

		return Form::hidden($name, '').implode('<br/>', $inputs);
	}

} // End Sprig_Field_ManyToMany
