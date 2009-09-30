<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig foreign key field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
abstract class Sprig_Field_ForeignKey extends Sprig_Field {

	public $null = TRUE;

	public $model;

} // End Sprig_Field_ForeignKey