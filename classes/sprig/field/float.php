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
		if (is_int($this->places))
		{
			$value = number_format($value, $this->places);
		}
		else
		{
			$value = (float) $value;
		}

		return parent::set($value);
	}

} // End Sprig_Field_Float