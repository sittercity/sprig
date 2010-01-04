<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig image field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @author     Kelvin Luck
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
class Sprig_Field_Image extends Sprig_Field_Char {

	/**
	 * @var  integer  image width
	 */
	public $width;

	/**
	 * @var  integer  image height
	 */
	public $height;

	/**
	 * @var  string  directory where the image will be loaded from
	 */
	public $directory;

	/**
	 * @var  integer  one of the Image resize constants
	 */
	public $resize = Image::AUTO;

	public function __construct(array $options = NULL)
	{
		if (empty($options['directory']) OR ! is_dir($options['directory']))
		{
			throw new Sprig_Exception('Image fields must define a directory path');
		}

		// Normalize the directory path
		$options['directory'] = rtrim(str_replace(array('\\', '/'), '/', $options['directory']), '/').'/';

		parent::__construct($options);
	}

	public function input($name, $value, array $attr = NULL)
	{
		return Form::file($name, $attr);
	}

	public function verbose($value)
	{
		return $this->directory.$value;
	}

} // End Sprig_Field_Image
