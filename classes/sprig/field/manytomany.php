<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig "has and belongs to many" relationship field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
class Sprig_Field_ManyToMany extends Sprig_Field_HasMany {

	public $default = array();

	public $column = '';

	public $through;

	public $rules = array('is_array' => NULL);

	public function set($value)
	{
		return parent::set((array) $value);
	}

	public function raw()
	{
		return $this->value->as_array();
	}

	public function input($name, array $attr = NULL)
	{
		$inputs = array();

		foreach ($this->choices as $value => $label)
		{
			$inputs[] = form::checkbox("{$name}[]", $value, in_array($value, $this->value)).' '.$label;
		}

		return $inputs;
	}

} // End Sprig_Field_ManyToMany
