<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig foreign key field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
abstract class Sprig_Field_ForeignKey extends Sprig_Field_Char {

	public $null = TRUE;

	public $in_db = FALSE;

	public $model;

	public $foreign_key = NULL;

	public $primary_key = NULL;

	// TODO: Document, this one is undocumented
	public $foreignkey_valuefield; // a field in the related model

	public function value($value)
	{
		if (is_object($value))
		{
			// Assume this is a Sprig object
			$return = array();
			if(isset($this->foreignkey_valuefield))
			{
				foreach($this->foreignkey_valuefield as $pk)
				{
					$return[] = $value->{$pk};
				}
			}
			else
			{
				foreach($value->pk_as_array() as $pk)
				{
					$return[] = $value->{$pk};
				}
			}
			$value = implode('-', $return);
			//$value = $value->{$value->pk()};
		}

		return parent::value($value);
	}

} // End Sprig_Field_ForeignKey