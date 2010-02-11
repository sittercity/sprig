<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig native timestamp field.
 *
 * @package    Sprig
 * @author     Marcus Cobden
 * @copyright  (c) 2010 Marcus Cobden
 * @license    MIT
 */
class Sprig_Field_NativeTimestamp extends Sprig_Field_Timestamp {

	public function _database_wrap($value)
	{
		return new Database_Expression('FROM_UNIXTIME('. (int) $value . ')');
	}
	
	public function _database_unwrap($value)
	{
		return new Database_Expression('UNIX_TIMESTAMP(' . $value . ')');
	}

} // End Sprig_Field_NativeTimestamp
