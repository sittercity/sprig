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
	 * @var  string  path where the image will be saved to/ loaded from
	 */
	public $path;

	/**
	 * @var  integer  one of the Image resize constants
	 */
	public $resize = Image::AUTO;

	public function __construct(array $options = NULL)
	{
		if (empty($options['path']) OR ! is_dir($options['path']))
		{
			throw new Sprig_Exception('Image fields must have a directory path to save and load images from');
		}

		parent::__construct($options);

		// Make sure the path has a trailing slash
		$this->path = rtrim(str_replace('\\', '/', $this->path), '/').'/';
	}

	public function input($name, $value, array $attr = NULL)
	{
		$input = Form::file($name, $attr);

		if ($value)
		{
			$input .= HTML::image($this->verbose($value));
		}

		return $input;
	}

	public function verbose($value)
	{
		return $this->path.$value;
	}

} // End Sprig_Field_Image
