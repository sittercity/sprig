<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig digit (integer) field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
class Sprig_Field_Integer extends Sprig_Field {

	public $min_value;

	public $max_value;

	public function value($value)
	{
		if ( ! empty($value))
		{
			$value = (int) $value;
		}

		return parent::value($value);
	}

} // End Sprig_Field_Integer