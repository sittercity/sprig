<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig floating point number field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
class Sprig_Field_Float extends Sprig_Field {

	public $places;

	public function set($value)
	{
		$value = (float) $value;
		return parent::set($value);
	}
	
	public function verbose()
	{
		return number_format($this->value, $this->places);
	}

} // End Sprig_Field_Float