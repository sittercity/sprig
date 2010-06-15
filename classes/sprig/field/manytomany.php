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
	 * Provides the original values for a Sprig Field if they are not set.  This
	 * is only applicable for certain Sprig Fields.
	 *
	 * @return array|null Returns an array of the original values, or NULL if
	 *                    not applicable
	 */
	public function set_original()
	{
		$model = Sprig::factory($this->model);

		if (isset($this->foreign_key) AND $this->foreign_key)
		{
			$fk = $this->foreign_key;
		}
		else
		{
			$fk = $model->fk();
		}

		$parent = $this->object;
		$pk = $parent->pk();
		$result = DB::select(
				array(
					$model->field($model->pk())->_database_unwrap($fk),
					$model->fk())
				)
			->from($this->through)
			->where(
				$fk,
				'=',
				$parent->field($pk)->_database_wrap($parent->{$pk}))
			->execute($parent->db());

		// The original value for the relationship must be defined
		// before we can tell if the value has been changed
		return $this->value($result->as_array(NULL, $model->fk()));
	}

	/**
	 * Provides the related values for a Sprig Field if they are not set.  This
	 * is only applicable for certain Sprig Fields.
	 *
	 * @param mixed $value The value or values to set
	 *
	 * @return array|null Returns an array of the related values, or NULL if
	 *                    not applicable
	 *
	 * @throws Sprig_Exception Exception thrown if attempting to replace a
	 *                         Sprig relationship that does not support being
	 *                         overridden.
	 */
	public function set_related($value)
	{
		// For some Sprig Fields, setting the related values is not necessary
		// Pass
		return NULL;
	}

	/**
	 * Produces a Select Query for retrieving this HasMany ForeignKey Field
	 * relationship based on the current scalar $value.
	 *
	 * @param Sprig $model A prototype instance of $this->model
	 * @param mixed $value The current scalar value of this Field
	 *
	 * @return Database_Query_Builder_Select|null
	 */
	protected function related_query(Sprig $model, $value)
	{
		if ($value instanceof Sprig_Void)
		{
			// We can grab the PK from the field definition.
			// If it doesn't exist, revert to the model choice
			$parent = $this->object;
			if (isset($this->foreign_key) AND $this->foreign_key)
			{
				$fk = $this->through.'.'.$this->foreign_key;
				$fk2 = $this->through.'.'.$model->pk();
			}
			else
			{
				$fk = $parent->fk($this->through);
				$fk2 = $model->fk($this->through);
			}

			$pk = $parent->pk();
			$query = DB::select()
				->join($this->through)
					->on($fk2, '=', $model->pk(TRUE))
				->where(
					$fk,
					'=',
					$parent->field($pk)->_database_wrap($parent->{$pk}));
		}
		else
		{
			if (empty($value))
			{
				return null;
			}
			else
			{
				// TODO this needs testing
				$wrapped = array_map(
					array($model->field($model->pk()),'_database_wrap'),
					$value);
				$query = DB::select()
					->where($model->pk(), 'IN', $wrapped);
			}
		}
		return $query;
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
