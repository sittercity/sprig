<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig image field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
class Sprig_Field_Image extends Sprig_Field_Upload {

	/**
	 * @var  integer  image width
	 */
	public $width;

	/**
	 * @var  integer  image height
	 */
	public $height;

	/**
	 * @var  integer  one of the Image resize constants: Image::AUTO, Image::NONE, etc
	 */
	public $resize;

	/**
	 * Resize the given image to the proper width and height.
	 *
	 * Automatically called by Sprig::_upload_file().
	 *
	 * @param   string   image file path
	 * @return  void
	 */
	public function resize($value)
	{
		if ($this->resize AND ($this->width OR $this->height))
		{
			Image::factory($value)
				->resize($this->width, $this->height, $this->resize)
				->save();
		}
	}

} // End Sprig_Field_Image
