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
		$value = parent::set($value);

		if ($value !== NULL)
		{
			$value = (float) $value;
		}

		return $value;
	}

	public function verbose($value)
	{
		if (is_float($value))
		{
			if ($this->places)
			{
				return number_format($value, $this->places);
			}
			else
			{
				return (string) $value;
			}
		}
		else
		{
			return '';
		}
	}

} // End Sprig_Field_Float
