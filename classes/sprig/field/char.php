<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig string (character) field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
class Sprig_Field_Char extends Sprig_Field {

	public $min_length;

	public $max_length;

	/**
	 * Casts a raw data value to the PHP char (string) data type
	 *
	 * @param mixed $value The raw data value
	 *
	 * @return string
	 */
	public function value($value)
	{
		if ($this->null AND empty($value))
		{
			// Empty values are converted to NULLs
			$value = null;
		}
		else if ( ! is_string($value) )
		{
			$value = (string) $value;
		}

		return $value;
	}

} // End Sprig_Field_Char
