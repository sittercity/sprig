<?php defined('SYSPATH') or die('No direct script access.');

class Sprig_Field_Text extends Sprig_Field_Char {

	public function input($name, array $attr = NULL)
	{
		return Form::textarea($name, $this->verbose(), $attr);
	}

} // End Sprig_Field_Text
