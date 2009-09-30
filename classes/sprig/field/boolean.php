<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig boolean field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
class Sprig_Field_Boolean extends Sprig_Field {

	public $empty = TRUE;

	public $default = FALSE;

	public $filters = array('filter_var' => array(FILTER_VALIDATE_BOOLEAN));

	public function set($value)
	{
		return parent::set((bool) $value);
	}

	public function verbose()
	{
		return $this->value ? 'Yes' : 'No';
	}

	public function input($name, array $attr = NULL)
	{
		return form::checkbox($name, 1, $this->value, $attr).' '.$this->label;
	}

} // End Sprig_Field_Boolean