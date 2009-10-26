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
	
	public $append_label = TRUE;

	public function set($value)
	{
		return parent::set((bool) $value);
	}

	public function verbose()
	{
		return $this->value ? 'Yes' : 'No';
	}

	public function input($name, $value, array $attr = NULL)
	{
		$checkbox = Form::checkbox($name, 1, $value, $attr);
		if ($this->append_label)
		{
			$checkbox .= ' '.$this->label;
		}
		return  $checkbox;
	}

} // End Sprig_Field_Boolean