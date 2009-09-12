<?php defined('SYSPATH') or die('No direct script access.');

class Sprig_Field_Image extends Sprig_Field_Char 
{
	const RESIZE_TYPE_CROP = 'RESIZE_TYPE_CROP';
	const RESIZE_TYPE_FIT = 'RESIZE_TYPE_FIT';
	
	public $width;
	public $height;
	public $path;
	public $resize_type;

	public function input($name, array $attr = array())
	{
		$attr['type'] = 'file';
		$r = Form::input($name, '', $attr);
		if ($this->verbose() != '') {
			$r .= HTML::image($this->path . $this->verbose());
		}
		return $r;
	}

} // End Sprig_Field_Image
