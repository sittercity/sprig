<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig "has and belongs to many" relationship field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
class Sprig_Field_ManyToMany extends Sprig_Field_HasMany {

	public $editable = TRUE;

	public $through;

	// This fk
	public $left_foreign_key = NULL;

	// other model's fk
	public $right_foreign_key = NULL;

	// Overload __construct to support legacy foreign_key fields
	public function __construct(array $options = NULL)
	{
		parent::__construct($options);

		if (isset($this->foreign_key))
			$this->left_foreign_key = $this->foreign_key;
	}

} // End Sprig_Field_ManyToMany
