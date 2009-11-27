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
	 * @const string Image will be cropped to the passed size (via Image->crop);
	 */
	const RESIZE_TYPE_CROP = 'RESIZE_TYPE_CROP';

	/**
	 * @const string Image will be resized to fit in the passed size (via Image->resize);
	 */
	const RESIZE_TYPE_FIT = 'RESIZE_TYPE_FIT';

	/**
	 * @const string Image will be resized and cropped to fit exactly in the passed size
	 * while retaining as much of the image as possible (via Image->resizeAndCrop);
	 */
	const RESIZE_TYPE_EXACT_FIT = 'RESIZE_TYPE_EXACT_FIT';

	/**
	 * @const string Image will not be cropped or resized at all and an Exception will
	 * be thrown if the uploaded image isn't the correct size.
	 */
	const RESIZE_TYPE_EXACT = 'RESIZE_TYPE_EXACT';

	/**
	 * @const string Image will not be cropped or resized at all
	 */
	const RESIZE_TYPE_NONE = 'RESIZE_TYPE_NONE';

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
	 * @var  string one of the RESIZE_TYPE_* constants
	 */
	public $resize_type;

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
