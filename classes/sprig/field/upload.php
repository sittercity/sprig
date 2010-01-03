<?php defined('SYSPATH') or die('No direct script access.');

class Sprig_Field_Upload extends Sprig_Field_Char {

	/**
	 * @var  string  directory to upload file to
	 */
	public $directory = 'upload';

	/**
	 * @var  mixed  name the file using a field: TRUE to use the title key, FALSE to use the original file name, or another field name string
	 */
	public $field_name = TRUE;

	/**
	 * @var  array  list of allowed file types: jpg, gif, doc, zip, etc
	 */
	public $types;

	/**
	 * @var  string  maximum allowed upload size: 2M, 1G, 500K, etc
	 */
	public $size;

	public function __construct(array $options = NULL)
	{
		if (empty($options['directory']) OR ! is_dir($options['directory']) OR ! is_writable($options['directory']))
		{
			throw new Sprig_Exception('Upload fields must have a writable directory');
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
		if ($value AND is_string($value) AND is_file($this->directory.$value))
		{
			return $this->directory.$value;
		}

		return NULL;
	}

} // End Sprig_Field_Upload
