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

	// Model many-to-many relations
	protected static $_relations;

	public $editable = TRUE;

	public $through;

	/**
	 * Initialize the Sprig Field. This method will only be called once by
	 * Sprig.
	 *
	 * @param Sprig  $object     The parent object which contains $this Field
	 * @param string $field_name The name of $this Field on the parent object
	 *
	 * @return null
	 */
	public function init(Sprig $object, $field_name)
	{
		parent::init($object, $field_name);

		// Initialize the $this->through property
		$this->through();
	}

	/**
	 * Get the public $this->through property, and build it first if need be.
	 *
	 * @return string
	 */
	protected function through()
	{
		if ( ! $this->through )
		{
			// Get the model names for the relation pair
			$pair = array(
				strtolower($this->object->model()),
				strtolower($this->model)
			);

			// Sort the model names alphabetically
			sort($pair);

			// Join the model names to get the relation name
			$pair = implode('_', $pair);

			if ( ! isset(Sprig_Field_ManyToMany::$_relations[$pair]) )
			{
				// Must set the pair key before loading the related model
				// or we will fall into an infinite recursion loop
				Sprig_Field_ManyToMany::$_relations[$pair] = TRUE;

				$tables = array(
					$this->object->table(),
					Sprig::factory($this->model)->table()
				);

				// Sort the table names alphabetically
				sort($tables);

				// Join the table names to get the table name
				Sprig_Field_ManyToMany::$_relations[$pair]
					= implode('_', $tables);
			}

			// Assign by reference so that changes to the pivot table
			// will carry over to all models
			$this->through =& Sprig_Field_ManyToMany::$_relations[$pair];
		}
		return $this->through;
	}

} // End Sprig_Field_ManyToMany
