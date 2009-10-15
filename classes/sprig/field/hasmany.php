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

	public $default = NULL;

	public function set($value)
	{
		if (empty($value) AND $this->empty)
		{
			$value = NULL;
		}

		$this->value = $value;
	}

	public function verbose()
	{
		$value = $this->raw();

		if (is_object($value))
		{
			$pk = Sprig::factory($this->model)->pk();
			$value = $value->as_array($pk, $pk);
		}

		return is_array($value) ? implode(', ', $value) : '';
	}

	public function input($name, array $attr = NULL)
	{
		// Load the model
		$model = Sprig::factory($this->model);

		if (is_object($this->value))
		{
			$selected = $this->value->as_array($model->pk(), $model->tk());
		}
		else
		{
			$selected = array();
		}

		$options = $model->select_list();

		$inputs = array();

		foreach ($options as $value => $label)
		{
			$inputs[] = Form::checkbox("{$name}[]", $value, in_array($value, $selected)).' '.$label;
		}

		return $inputs;
	}

} // End Sprig_Field_ManyToMany
