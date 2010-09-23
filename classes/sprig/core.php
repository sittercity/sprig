<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig database modeling system.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
abstract class Sprig_Core {

	const VERSION = '1.1.1';

	// Model many-to-many relations
	protected static $_relations;

	/**
	 * Load an empty sprig model.
	 *
	 * @param   string  model name
	 * @param   array   values to pre-populate the model
	 * @return  Sprig
	 */
	public static function factory($name, array $values = NULL)
	{
		$model = 'Model_'.$name;
		$model = new $model;

		if ($values)
		{
			$model->values($values);
		}

		return $model;
	}

	/**
	 * @var  string  model name
	 */
	protected $_model;

	/**
	 * @var  string  database instance name
	 */
	protected $_db = 'default';

	/**
	 * @var  string  database table name
	 */
	protected $_table;

	/**
	 * @var  array  field list (name => object)
	 */
	protected $_fields = array();

	/**
	 * @var  mixed  primary key string or array (for composite keys)
	 */
	protected $_primary_key;

	/**
	 * @var  string  title key string (for select lists)
	 */
	protected $_title_key = 'name';

	/**
	 * @var  array  default sorting parameters
	 */
	protected $_sorting;

	// Initialization status
	protected $_init = FALSE;

	// Object state
	protected $_state = 'new';

	// Original object data
	protected $_original = array();

	// Changed object data
	protected $_changed = array();

	// Related object data
	protected $_related = array();

	/**
	 * Initialize the fields and add validation rules based on field properties.
	 *
	 * @return  void
	 */
	protected function __construct()
	{
		if ($this->_init)
		{
			if ($this->state() === 'loading')
			{
				// Object loading via mysql_fetch_object or similar has finished
				$this->state('loaded');
			}

			// Can only be called once
			return;
		}

		// Initialization has been started
		$this->_init = TRUE;

		// Set up the fields
		$this->_init();

		if ( ! $this->_model)
		{
			// Set the model name based on the class name
			$this->_model = strtolower(substr(get_class($this), 6));
		}

		if ( ! $this->_table)
		{
			// Set the table name to the plural model name
			$this->_table = inflector::plural($this->_model);
		}

		foreach ($this->_fields as $name => $field)
		{
			if ($field->primary === TRUE)
			{
				if ( ! $this->_primary_key)
				{
					// This is the primary key
					$this->_primary_key = $name;
				}
				else
				{
					if (is_string($this->_primary_key))
					{
						// More than one primary key found, create a list of keys
						$this->_primary_key = array($this->_primary_key);
					}

					// Add this key to the list
					$this->_primary_key[] = $name;
				}
			}
		}

		foreach ($this->_fields as $name => $field)
		{
			if ($field instanceof Sprig_Field_ForeignKey AND ! $field->model)
			{
				if ($field instanceof Sprig_Field_HasMany)
				{
					$field->model = Inflector::singular($name);
				}
				else
				{
					$field->model = $name;
				}
			}

			if ($field instanceof Sprig_Field_ManyToMany)
			{
				if ( ! $field->through)
				{
					// Get the model names for the relation pair
					$pair = array(strtolower($this->_model), strtolower($field->model));

					// Sort the model names alphabetically
					sort($pair);

					// Join the model names to get the relation name
					$pair = implode('_', $pair);

					if ( ! isset(Sprig::$_relations[$pair]))
					{
						// Must set the pair key before loading the related model
						// or we will fall into an infinite recursion loop
						Sprig::$_relations[$pair] = TRUE;

						$tables = array($this->table(), Sprig::factory($field->model)->table());

						// Sort the table names alphabetically
						sort($tables);

						// Join the table names to get the table name
						Sprig::$_relations[$pair] = implode('_', $tables);
					}

					// Assign by reference so that changes to the pivot table
					// will carry over to all models
					$field->through =& Sprig::$_relations[$pair];
				}
			}

			if ( ! $field->column)
			{
				// Create the key based on the field name

				if ($field instanceof Sprig_Field_BelongsTo)
				{
					if ( isset($field->foreign_key) AND $field->foreign_key)
					{
						$fk = $field->foreign_key;
					}
					else
					{
						$fk = Sprig::factory($field->model)->fk();
					}

					$field->column = $fk;
				}
				elseif ($field instanceof Sprig_Field_HasOne)
				{
					$field->column = $this->fk();
				}
				elseif ($field instanceof Sprig_Field_ForeignKey)
				{
					// This field is probably a Many and does not need a column
				}
				else
				{
					$field->column = $name;
				}
			}

			if ( ! $field->label)
			{
				$field->label = Inflector::humanize($name);
			}

			if ($field->null)
			{
				// Fields that allow NULL values must accept empty values
				$field->empty = TRUE;
			}

			if ($field->editable)
			{
				if ( ! $field->empty AND ! isset($field->rules['not_empty']))
				{
					// This field must not be empty
					$field->rules['not_empty'] = NULL;
				}

				if ($field->unique)
				{
					// Field must be a unique value
					$field->callbacks[] = array($this, '_unique_field');
				}

				if ($field->choices AND ! isset($field->rules['in_array']))
				{
					// Field must be one of the available choices
					$field->rules['in_array'] = array(array_keys($field->choices));
				}

				if ( ! empty($field->min_length))
				{
					$field->rules['min_length'] = array($field->min_length);
				}

				if ( ! empty($field->max_length))
				{
					$field->rules['max_length'] = array($field->max_length);
				}
			}

			if ($field instanceof Sprig_Field_BelongsTo OR ! $field instanceof Sprig_Field_ForeignKey)
			{
				// Set the default value for any field that is stored in the database
				$this->_original[$name] = $field->value($field->default);
			}
		}
	}

	/**
	 * Returns the model name.
	 *
	 * @return  string
	 */
	public function __toString()
	{
		return $this->_model;
	}

	/**
	 * Get the value of a field.
	 *
	 * @throws  Sprig_Exception  field does not exist
	 * @param   string  field name
	 * @return  mixed
	 */
	public function __get($name)
	{
		if ( ! $this->_init)
		{
			// The constructor must always be called first
			$this->__construct();

			// This object is about to be loaded by mysql_fetch_object() or similar
			$this->state('loading');
		}

		if ( ! isset($this->_fields[$name]))
		{
			throw new Sprig_Exception(':name model does not have a field :field',
				array(':name' => get_class($this), ':field' => $name));
		}

		if (isset($this->_related[$name]))
		{
			// Shortcut to any related object
			return $this->_related[$name];
		}

		$field = $this->_fields[$name];

		if ($this->changed($name))
		{
			$value = $this->_changed[$name];
		}
		elseif (array_key_exists($name, $this->_original))
		{
			$value = $this->_original[$name];
		}

		if ($field instanceof Sprig_Field_ForeignKey)
		{
			if ( ! isset($this->_related[$name]))
			{
				$model = Sprig::factory($field->model);

				if ($field instanceof Sprig_Field_HasMany)
				{
					if ($field instanceof Sprig_Field_ManyToMany)
					{
						if (isset($value))
						{
							if (empty($value))
							{
								return new Database_Result_Cached(array(), '');
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
						else
						{
							// We can grab the PK from the field definition.
							// If it doesn't exist, revert to the model choice
							if ( isset($field->left_foreign_key) AND $field->left_foreign_key)
							{
								$fk = $field->through.'.'.$field->left_foreign_key;
								$fk2 = $field->through.'.'.$model->pk();
							}
							else
							{
								$fk = $this->fk($field->through);
								$fk2 = $model->fk($field->through);
							}

							$query = DB::select()
								->join($field->through)
									->on($fk2, '=', $model->pk(TRUE))
								->where(
									$fk,
									'=',
									$this->_fields[$this->_primary_key]->_database_wrap($this->{$this->_primary_key}));
						}
					}
					else
					{
						if (isset($value))
						{
							$query = DB::select()
								->where(
									$model->pk(),
									'=',
									$field->_database_wrap($value));
						}
						else
						{
							if ( isset($field->foreign_key) AND $field->foreign_key)
							{
								$fk = $field->foreign_key;
							}
							else
							{
								$fk = $model->fk();
							}

							$query = DB::select()
								->where(
									$fk,
									'=',
									$this->_fields[$this->_primary_key]->_database_wrap($this->{$this->_primary_key}));
						}
					}

					$related = $model->load($query, NULL);

					if ( ! $this->changed($name))
					{
						// We can assume this is the original value because no
						// changed value exists
						$this->_original[$name] = $field->value($related);
					}
				}
				elseif ($field instanceof Sprig_Field_BelongsTo)
				{
					if ( isset($field->primary_key) AND $field->primary_key)
						$pk = $field->primary_key;
					else
						$pk = $model->pk();

					$related = $model->values(array($pk => $value));
				}
				elseif ($field instanceof Sprig_Field_HasOne)
				{
					$related = $model->values(array($this->_model => $this->{$this->_primary_key}));
				}

				$value = $this->_related[$name] = $related;
			}
		}

		return $value;
	}

	/**
	 * Set the value of a field.
	 *
	 * @throws  Sprig_Exception  field does not exist
	 * @param   string  field name
	 * @param   mixed   new field value
	 * @return  void
	 */
	public function __set($name, $value)
	{
		if ( ! $this->_init)
		{
			// The constructor must always be called first
			$this->__construct();

			// This object is about to be loaded by mysql_fetch_object() or similar
			$this->state('loading');
		}

		if ( ! isset($this->_fields[$name]))
		{
			throw new Sprig_Exception(':name model does not have a field :field',
				array(':name' => get_class($this), ':field' => $name));
		}

		// Get the field object
		$field = $this->_fields[$name];

		if ($this->state() === 'loading')
		{
			// Set the original value directly
			$this->_original[$name] = $field->value($value);

			// No extra processing necessary
			return;
		}
		elseif ($field instanceof Sprig_Field_ManyToMany)
		{
			if ( ! isset($this->_original[$name]))
			{
				$model = Sprig::factory($field->model);

				if ( isset($field->left_foreign_key) AND $field->left_foreign_key)
				{
					$fk = $field->left_foreign_key;
				}
				else
				{
					$fk = $model->fk();
				}

				$result = DB::select(
						array(
							$model->field($model->pk())->_database_unwrap($fk),
							$model->fk())
						)
					->from($field->through)
					->where(
						$fk,
						'=',
						$this->_fields[$this->_primary_key]->_database_wrap($this->{$this->_primary_key}))
					->execute($this->_db);

				// The original value for the relationship must be defined
				// before we can tell if the value has been changed
				$this->_original[$name] = $field->value($result->as_array(NULL, $model->fk()));
			}
		}
		elseif ($field instanceof Sprig_Field_HasMany)
		{
			foreach ($value as $key => $val)
			{
				if ( ! $val instanceof Sprig)
				{
					$model = Sprig::factory($field->model);
					$pk    = $model->pk();

					if ( ! is_array($val))
					{
						// Assume the value is a primary key
						$val = array($pk => $val);
					}

					if (isset($val[$pk]))
					{
						// Load the record so that changed values can be determined
						$model->values(array($pk => $val[$pk]))->load();
					}

					$value[$key] = $model->values($val);
				}
			}

			// Set the related objects to this value
			$this->_related[$name] = $value;

			// No extra processing necessary
			return;
		}
		elseif ($field instanceof Sprig_Field_BelongsTo)
		{
			// Pass
		}
		elseif ($field instanceof Sprig_Field_ForeignKey)
		{
			throw new Sprig_Exception('Cannot change relationship of :model->:field using __set()',
				array(':model' => $this->_model, ':field' => $name));
		}

		// Get the correct type of value
		$changed = $field->value($value);

		if (isset($field->hash_with) AND $changed)
		{
			$changed = call_user_func($field->hash_with, $changed);
		}

		$re_changed = (array_key_exists($name, $this->_changed) &&
			$changed !== $this->_changed[$name]);
		$original = $changed === $this->_original[$name];
		if ($re_changed OR ! $original)
		{
			if (isset($this->_related[$name]))
			{
				// Clear stale related objects
				unset($this->_related[$name]);
			}

			if ($original)
			{
				// Simply pretend the change never happened
				unset($this->_changed[$name]);
			}
			else
			{
				// Set a changed value
				$this->_changed[$name] = $changed;
			
				if ($field instanceof Sprig_Field_ForeignKey
					AND is_object($value))
				{
					// Store the related object for later use
					$this->_related[$name] = $value;
				}
			}
		}
	}

	/**
	 * Check if a value exists within the mode.
	 *
	 * @param   string   field name
	 * @return  boolean
	 */
	public function __isset($name)
	{
		return isset($this->_fields[$name]);
	}

	/**
	 * Unset the changed the value of a field.
	 *
	 * @throws  Sprig_Exception  field does not exist
	 * @param   string  field name
	 * @return  void
	 */
	public function __unset($name)
	{
		if ( ! $this->_init)
		{
			// The constructor must always be called first
			$this->__construct();
		}

		if ( ! isset($this->_fields[$name]))
		{
			throw new Sprig_Exception(':name model does not have a field :field',
				array(':name' => get_class($this), ':field' => $name));
		}

		$field = $this->_fields[$name];

		if ($field->in_db)
		{
			// Set the original value back to the default
			$this->_original[$name] = $field->value($field->default);
		}

		// Remove any changed value
		unset($this->_changed[$name]);
	}

	/**
	 * Allow serialization of initialized object containing related objects as a Database_Result
	 * @return array	list of properties to serialize
	 */
	public function __sleep()
	{
		foreach ($this->_related as $field => $object)
		{
			if ($object instanceof Database_Result)
			{
				if ($object instanceof Database_Result_Cached)
				{
					continue;
				}

				// Convert result object to cached result to allow for serialization
				// Currently no way to get the $_query property form the result to pass to the cached result
				// @see http://dev.kohanaphp.com/issues/2297

				$this->_related[$field] = new Database_Result_Cached($object->as_array(), '');
			}
		}

		// Return array of all properties to get them serialised
		$props = array();

		foreach ($this as $prop => $val)
		{
			$props[] = $prop;
		}

		return $props;
	}

	/**
	 * Adds a relationship to the model
	 * 
	 * @param  string  field name to add the relationship to
	 * @param  mixed   model to add, can be in integer model ID, a single model object, or an array of integers
	 * 
	 * @throws Sprig_Exception  on invalid relationship or model arguments
	 *
	 * @return $this
	 */
	public function add($name, $value)
	{
		if ( ! isset($this->_original[$name]) OR ! is_array($this->_original[$name]))
		{
			throw new Sprig_Exception('Unknown relationship: :name', array(':name' => $name));
		}

		$values = array();
		if (is_object($value))
		{
			$values[$value->{$value->pk()}] = $value->{$value->pk()};
		}
		elseif(is_numeric($value))
		{
			$values[$value] = $value;
		}
		elseif(is_array($value))
		{
			$values = $value;
		}
		else
		{
			throw new Sprig_Exception('Invalid data type: :value', array(':value' => $value));
		}

		$this->$name = $this->_original[$name]+$values;

		return $this;
	}

	/**
	 * Removes a relationship to the model
	 * 
	 * @param  string  field name to add the relationship to
	 * @param  mixed   model to remove, can be in integer model ID, a single model object, or an array of integers
	 * 
	 * @throws Sprig_Exception  on invalid relationship or model arguments
	 *
	 * @return $this
	 */
	public function remove($name, $value)
	{
		if ( ! isset($this->_original[$name]) OR ! is_array($this->_original[$name]))
		{
			throw new Sprig_Exception('Unknown relationship: :name', array(':name' => $name));
		}

		$values = array();
		if (is_object($value))
		{
			$values[$value->{$value->pk()}] = $value->{$value->pk()};
		}
		elseif(is_numeric($value))
		{
			$values[$value] = $value;
		}
		elseif(is_array($value))
		{
			$values = $value;
		}
		else
		{
			throw new Sprig_Exception('Invalid data type: :value', array(':value' => $value));
		}

		$original = $this->_original[$name];
		foreach ($values as $value)
		{
			unset($original[$value]);
		}

		$this->$name = $original;

		return $this;
	}

	/**
	 * Returns the primary key of the model, optionally with a table name.
	 *
	 * @param   string  table name, TRUE for the model table
	 * @return  string
	 */
	public function pk($table = NULL)
	{
		if ($table)
		{
			if ($table === TRUE)
			{
				$table = $this->_table;
			}

			return $table.'.'.$this->_primary_key;
		}

		return $this->_primary_key;
	}

	/**
	 * Returns the foreign key of the model, optionally with a table name.
	 *
	 * @param   string  table name, TRUE for the model table
	 * @return  string
	 */
	public function fk($table = NULL)
	{
		$key = $this->_model.'_'.$this->_primary_key;

		if ($table)
		{
			if ($table === TRUE)
			{
				$table = $this->_table;
			}

			return $table.'.'.$key;
		}

		return $key;
	}

	/**
	 * Returns the title key of the model, optionally with a table name.
	 *
	 * @param   string  table name, TRUE for the model table
	 * @return  string
	 */
	public function tk($table = NULL)
	{
		if ($table)
		{
			if ($table === TRUE)
			{
				$table = $this->_table;
			}

			return $table.'.'.$this->_title_key;
		}

		return $this->_title_key;
	}

	/**
	 * Gets and sets the database instance used for this model.
	 *
	 * @return  string
	 */
	public function db($db = NULL)
	{
		if ($db)
		{
			$this->_db = $db;
		}

		return $this->_db;
	}

	/**
	 * Gets and sets the table name of the model.
	 *
	 * @param   string   new table name
	 * @return  string   table name
	 */
	public function table($table = NULL)
	{
		if ($table)
		{
			$this->_table = $table;
		}

		return $this->_table;
	}

	/**
	 * Load all of the values in an associative array. Ignores all fields are
	 * not in the model.
	 *
	 * @param   array    field => value pairs
	 * @return  $this
	 */
	public function values(array $values)
	{
		// Remove all values which do not have a corresponding field
		$values = array_intersect_key($values, $this->_fields);

		foreach ($values as $field => $value)
		{
			$this->$field = $value;
		}

		return $this;
	}

	/**
	 * Get the model data as an associative array.
	 *
	 * @return  array  field => value
	 */
	public function as_array($verbose = FALSE)
	{
		$data = array_merge($this->_original, $this->_changed);

		if ($verbose)
		{
			foreach ($data as $field => $value)
			{
				// Convert each field to the verbose value
				$data[$field] = $this->_fields[$field]->verbose($value);
			}
		}

		return $data;
	}

	/**
	 * Get all of the records for this table as an associative array.
	 *
	 * @param   string  array key, defaults to the primary key
	 * @param   string  array value, defaults to the title key
	 * @return  array   key => value
	 */
	public function select_list($key = NULL, $value = NULL)
	{
		if ( ! $key)
		{
			$key = $this->pk();
		}

		if ( ! $value)
		{
			$value = $this->tk();
		}

		$query = DB::select($key, $value)
			->from($this->_table);

		if ($this->_sorting)
		{
			foreach ($this->_sorting as $field => $direction)
			{
				$query->order_by($field, $direction);
			}
		}

		return $query
			->execute($this->_db)
			->as_array($key, $value);
	}

	/**
	 * Get or set the model status.
	 *
	 * Setting the model status can have side effects. Changing the state to
	 * "loaded" will merge the currently changed data with the original data.
	 * Changing to "new" will reset the original data to the default values.
	 * Setting a "deleted" state will reset the changed data.
	 *
	 * Possible model states:
	 *
	 * - new:     record has not been created
	 * - deleted: record has been deleted
	 * - loaded:  record has been loaded
	 *
	 * @param   string  new object status
	 * @return  string  when getting
	 * @return  $this   when setting
	 */
	public function state($state = NULL)
	{
		if ($state)
		{
			switch ($state)
			{
				case 'new':
					// Reset original data
					$this->_original = Sprig::factory($this->_model)->as_array();
				break;
				case 'loaded':
					// Merge the changed data into the original data
					$this->_original = array_merge($this->_original, $this->_changed);
					$this->_changed  = array();
				break;
				case 'deleted':
				case 'loading':
					// Pass
				break;
				default:
					throw new Sprig_Exception('Unknown model state: :state', array(':state' => $state));
				break;
			}

			// Set the new state
			$this->_state = $state;

			return $this;
		}

		return $this->_state;
	}

	/**
	 * Object data loaded status.
	 *
	 * @return  boolean
	 */
	public function loaded()
	{
		return $this->_state === 'loaded';
	}

	/**
	 * Get all of the changed fields as an associative array.
	 *
	 * @return  array  field => value
	 */
	public function changed($field = NULL)
	{
		if ($field === NULL)
		{
			// Note that array_diff_assoc() can't be used here because it
			// assumes that any two array values are the same... WTF!

			$changed = $this->as_array();

			foreach ($changed as $field => $value)
			{
				if ( ! array_key_exists($field, $this->_changed))
				{
					unset($changed[$field]);
				}
			}

			return $changed;
		}
		else
		{
			return array_key_exists($field, $this->_changed);
		}
	}

	/**
	 * Get a single field object.
	 *
	 * @return  Sprig_Field
	 */
	public function field($name)
	{
		return $this->_fields[$name];
	}

	/**
	 * Get all fields as an associative array.
	 *
	 * @return  array  name => object
	 */
	public function fields()
	{
		return $this->_fields;
	}

	/**
	 * Return a single field input.
	 *
	 * @param   string  field name
	 * @param   array   input attributes
	 * @return  string
	 */
	public function input($name, array $attr = NULL)
	{
		$field = $this->_fields[$name];

		if ($attr === NULL)
		{
			$attr = $field->attributes;
		}

		return $field->input($name, $this->$name, $attr);
	}

	/**
	 * Get all fields as an array of inputs.
	 *
	 * @param   boolean  use the input label as the array key
	 * @return  array    label => input
	 */
	public function inputs($labels = TRUE)
	{
		$inputs = array();

		foreach ($this->_fields as $name => $field)
		{
			if ($field->editable)
			{
				if ($labels === TRUE)
				{
					$key = $field->label($name);
				}
				else
				{
					$key = $name;
				}

				$inputs[$key] = $field->input($name, $this->$name, $field->attributes);
			}
		}

		return $inputs;
	}

	/**
	 * Return a single field label.
	 *
	 * @param   string  field name
	 * @param   array   label attributes
	 * @return  string
	 */
	public function label($field, array $attr = NULL)
	{
		return $this->_fields[$field]->label($field, $attr);
	}

	/**
	 * Return a single field value in verbose form.
	 *
	 * @param   string  field name
	 * @return  string
	 */
	public function verbose($field)
	{
		return $this->_fields[$field]->verbose($this->$field);
	}

	/**
	 * Count the number of records using the current data.
	 *
	 * @param   object   any Database_Query_Builder_Select, NULL for none
	 * @return  $this
	 */
	public function count(Database_Query_Builder_Select $query = NULL)
	{
		if ( ! $query)
		{
			$query = DB::select();
		}

		$table = is_array($this->_table) ? $this->_table[1] : $this->_table;

		if ($changed = $this->changed())
		{
			foreach ($changed as $field => $value)
			{
				$field = $this->_fields[$field];

				if ( ! $field->in_db)
				{
					continue;
				}

				$query->where(
					"{$table}.{$field->column}",
					'=',
					$field->_database_wrap($value));
			}
		}

		return $query->select(array('COUNT("*")', 'total'))
			->from($this->_table)
			->execute($this->_db)
			->get('total');
	}

	/**
	 * Load a single record using the current data.
	 *
	 * @param   object   any Database_Query_Builder_Select, NULL for none
	 * @param   integer  number of records to load, FALSE for all
	 * @return  $this
	 */
	public function load(Database_Query_Builder_Select $query = NULL, $limit = 1)
	{
		// Load changed values as search parameters
		$changed = $this->changed();

		if ( ! $query)
		{
			$query = DB::select();
		}

		$query->from($this->_table);

		$table = is_array($this->_table) ? $this->_table[1] : $this->_table;

		foreach ($this->_fields as $name => $field)
		{
			if ( ! $field->in_db)
			{
				// Multiple relations cannot be loaded this way
				continue;
			}

			$query->select(array($field->_database_unwrap("{$table}.{$field->column}"), $name));

			if (array_key_exists($name, $changed))
			{
				$query->where(
					"{$table}.{$field->column}",
					'=',
					$field->_database_wrap($changed[$name]));
			}
		}

		if ($limit)
		{
			$query->limit($limit);
		}

		if ($this->_sorting)
		{
			foreach ($this->_sorting as $field => $direction)
			{
				$query->order_by($field, $direction);
			}
		}

		if ($limit === 1)
		{
			$result = $query
				->execute($this->_db);

			if (count($result))
			{
				$this->_changed = array();
				$this->state('loading')->values($result[0])->state('loaded');
			}

			return $this;
		}
		else
		{
			return $query
				->as_object(get_class($this))
				->execute($this->_db);
		}
	}


	/**
	 * Create a new record using the current data.
	 *
	 * @uses    Sprig::check()
	 * @return  $this
	 */
	public function create()
	{
		foreach ($this->_fields as $name => $field)
		{
			if ($field instanceof Sprig_Field_Timestamp AND $field->auto_now_create)
			{
				// Set the value to the current timestamp
				$this->$name = time();
			}
		}

		// Check the all current data
		$data = $this->check($this->as_array());

		$values = $relations = array();
		foreach ($data as $name => $value)
		{
			$field = $this->_fields[$name];

			if ($field instanceof Sprig_Field_Auto OR ! $field->in_db )
			{
				if ($field instanceof Sprig_Field_ManyToMany)
				{
					$relations[$name] = $value;
				}

				// Skip all auto-increment fields or where in_db is false
				continue;
			}

			// Change the field name to the column name
			$values[$field->column] = $field->_database_wrap($value);
		}

		list($id) = DB::insert($this->_table, array_keys($values))
			->values($values)
			->execute($this->_db);

		if (is_array($this->_primary_key))
		{
			foreach ($this->_primary_key as $name)
			{
				if ($this->_fields[$name] instanceof Sprig_Field_Auto)
				{
					// Set the auto-increment primary key to the insert id
					$this->$name = $id;

					// There can only be 1 auto-increment column per model
					break;
				}
			}
		}
		elseif ($this->_fields[$this->_primary_key] instanceof Sprig_Field_Auto)
		{
			$this->{$this->_primary_key} = $id;
		}

		// Object is now loaded
		$this->state('loaded');

		if ($relations)
		{
			foreach ($relations as $name => $value)
			{
				$field = $this->_fields[$name];

				if ( isset($field->foreign_key) AND $field->foreign_key)
				{
					$fk = $field->foreign_key;
				}
				else
				{
					$fk = $this->fk($field->through);
				}

				$model = Sprig::factory($field->model);

				foreach ($value as $id)
				{
					DB::insert($field->through, array($fk, $model->fk()))
						->values(
							array(
								$this->_fields[$this->_primary_key]->_database_wrap($this->{$this->_primary_key}),
								$model->field($model->pk())->_database_wrap($id))
							)
						->execute($this->_db);
				}
			}
		}

		return $this;
	}

	/**
	 * Update the current record using the current data.
	 *
	 * @uses    Sprig::check()
	 * @return  $this
	 */
	public function update()
	{
		if ($this->changed())
		{
			foreach ($this->_fields as $name => $field)
			{
				if ($field instanceof Sprig_Field_Timestamp AND $field->auto_now_update)
				{
					// Set the value to the current timestamp
					$this->$name = time();
				}
			}

			// Check the updated data
			$data = $this->check($this->changed());

			$values = $relations = array();
			foreach ($data as $name => $value)
			{
				$field = $this->_fields[$name];

				if ( ! $field->in_db)
				{
					if ($field instanceof Sprig_Field_ManyToMany)
					{
						// Relationships have been changed
						$relations[$name] = $value;
					}

					// Skip all fields that are not in the database
					continue;
				}

				// Change the field name to the column name
				$values[$field->column] = $field->_database_wrap($value);
			}

			if ($values)
			{
				$query = DB::update($this->_table)
					->set($values);

				if (is_array($this->_primary_key))
				{
					foreach($this->_primary_key as $field)
					{
						$query->where(
							$this->_fields[$field]->column,
							'=',
							$this->_fields[$field]->_database_wrap($this->_original[$field]));
					}
				}
				else
				{
					$query->where(
						$this->_fields[$this->_primary_key]->column,
						'=',
						$this->_fields[$this->_primary_key]->_database_wrap($this->_original[$this->_primary_key]));
				}

				$query->execute($this->_db);
			}

			if ($relations)
			{
				foreach ($relations as $name => $value)
				{
					$field = $this->_fields[$name];

					$model = Sprig::factory($field->model);

					if ( isset($field->left_foreign_key) AND $field->left_foreign_key)
					{
						$left_fk = $field->left_foreign_key;
					}
					else
					{
						$left_fk = $this->fk();
					}

					if ( isset($field->right_foreign_key) AND $field->right_foreign_key)
					{
						$right_fk = $field->right_foreign_key;
					}
					else
					{
						$right_fk = $model->fk();
					}

					// Find old relationships that must be deleted
					if ($old = array_diff($this->_original[$name], $value))
					{
						// TODO this needs testing
						$old = array_map(array($this->_fields[$this->_primary_key],'_database_wrap'), $old);

						DB::delete($field->through)
							->where(
								$left_fk,
								'=',
								$this->_fields[$this->_primary_key]->_database_wrap($this->{$this->_primary_key}))
							->where($right_fk, 'IN', $old)
							->execute($this->_db);
					}

					// Find new relationships that must be inserted
					if ($new = array_diff($value, $this->_original[$name]))
					{
						foreach ($new as $id)
						{
							DB::insert($field->through, array($left_fk, $right_fk))
								->values(
									array(
										$this->_fields[$this->_primary_key]->_database_wrap($this->{$this->_primary_key}),
										$model->field($model->pk())->_database_wrap($id)
									))
								->execute($this->_db);
						}
					}
				}
			}

			// Reset the original data for this record
			$this->_original = $this->as_array();

			// Everything has been updated
			$this->_changed = array();
		}

		return $this;
	}

	/**
	 * Delete the current record:
	 *
	 * - If the record is loaded, it will be deleted using primary key(s).
	 * - If the record is not loaded, it will be deleted using all changed fields.
	 * - If no data has been changed, the delete will be ignored.
	 *
	 * @param   object   any Database_Query_Builder_Delete, NULL for none
	 * @return  $this
	 */
	public function delete(Database_Query_Builder_Delete $query = NULL)
	{
		if ( ! $query)
		{
			$query = DB::delete($this->_table);
		}
		else
		{
			$query->table($this->_table);
		}

		if ($changed = $this->changed())
		{
			foreach ($changed as $field => $value)
			{
				$query->where(
					$this->_fields[$field]->column,
					'=',
					$this->_fields[$field]->_database_wrap($value));
			}
		}
		else
		{
			if (is_array($this->_primary_key))
			{
				foreach($this->_primary_key as $field)
				{
					$query->where(
						$this->_fields[$field]->column,
						'=',
						$this->_fields[$field]->_database_wrap($this->_original[$field]));
				}
			}
			else
			{
				$query->where(
					$this->_fields[$this->_primary_key]->column,
					'=',
					$this->_fields[$this->_primary_key]->_database_wrap($this->_original[$this->_primary_key]));
			}
		}

		if ($query->execute($this->_db))
		{
			$this->state('deleted');
		}

		return $this;
	}

	/**
	 * Check the given data is valid. Only values that have editable fields
	 * will be included and checked.
	 *
	 * @throws  Validate_Exception  when an error is found
	 * @param   array  data to check, field => value
	 * @return  array  filtered data
	 */
	public function check(array $data = NULL)
	{
		if ($data === NULL)
		{
			// Use the current data set
			$data = $this->changed();
		}

		$data = Validate::factory($data);

		foreach ($this->_fields as $name => $field)
		{
			if ( ! $data->offsetExists($name))
			{
				// Do not add any rules for this field
				continue;
			}

			$data->label($name, $field->label);

			if ($field->filters)
			{
				$data->filters($name, $field->filters);
			}

			if ($field->rules)
			{
				$data->rules($name, $field->rules);
			}

			if ($field->callbacks)
			{
				$data->callbacks($name, $field->callbacks);
			}
		}

		if ( ! $data->check())
		{
			throw new Validate_Exception($data);
		}

		return $data->as_array();
	}

	/**
	 * Callback for validating unique fields.
	 *
	 * @param   object  Validate array
	 * @param   string  field name
	 * @return  void
	 */
	public function _unique_field(Validate $array, $field)
	{
		if ($array[$field])
		{
			$query = DB::select($this->_fields[$this->_primary_key]->column)
				->from($this->_table)
				->where(
					$this->_fields[$field]->column,
					'=',
					$this->_fields[$field]->_database_wrap($array[$field]))
				->execute($this->_db);

			if (count($query))
			{
				$array->error($field, 'unique');
			}
		}
	}

	/**
	 * Initialize the fields. This method will only be called once
	 * by Sprig::init(). All models must define this method!
	 *
	 * @return  void
	 */
	abstract protected function _init();

} // End Sprig
