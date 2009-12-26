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
			// Assume this is a Database_Result object

			$result = array();
			foreach ($value as $model)
			{
				$row = array();
				foreach ($model->pk() as $pk)
				{
					$row[$pk] = $model->$pk;
				}
				$result[] = $row;
			}
			$value = $result;
		}
		else
		{
			// Value must always be an array
			$value = (array) $value;
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

} // End Sprig_Field_ManyToMany
