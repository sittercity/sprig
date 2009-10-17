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

	public $default = array();

	public function load()
	{
		$model = Sprig::factory($this->model);

		if ($this->value)
		{
			// Select all of the related models
			$query = DB::select()->where($model->pk(), 'IN', $this->value);
		}
		else
		{
			// Select nothing
			$query = DB::select()->limit(0);
		}

		return $model->load($query, FALSE);
	}

	public function set($value)
	{
		if (empty($value) AND $this->empty)
		{
			$value = array();
		}

		$this->value = $value;
	}

	public function verbose()
	{
		return implode(', ', $this->value);
	}

	public function input($name, array $attr = NULL)
	{
		$model = Sprig::factory($this->model);

		// All available options
		$options = $model->select_list($model->pk());

		$inputs = array();
		foreach ($options as $id => $label)
		{
			$inputs[] = '<label>'.Form::checkbox("{$name}[]", $id, isset($this->value[$id])).' '.$label.'</label>';
		}

		// Hidden input is added to force $_POST to contain a value for
		// this field, even when nothing is selected.

		return Form::hidden($name, '').implode('<br/>', $inputs);
	}

} // End Sprig_Field_ManyToMany
