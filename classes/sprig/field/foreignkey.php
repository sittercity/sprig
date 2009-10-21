<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig foreign key field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
abstract class Sprig_Field_ForeignKey extends Sprig_Field_Integer {

	public $null = TRUE;

	public $in_db = FALSE;

	public $model;

	public function __construct(array $options = NULL)
	{
		if ( ! isset($options['model']))
		{
			throw new Sprig_Exception('All foreign key fields must have an associated model');
		}

		parent::__construct($options);
	}

	public function value($value)
	{
		if (is_object($value))
		{
			// Assume this is a Sprig object
			$value = $value->{$value->pk()};
		}

		return parent::value($value);
	}

} // End Sprig_Field_ForeignKey