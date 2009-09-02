<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig database modeling system.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
abstract class Sprig {

	/**
	 * Load an empty sprig model.
	 *
	 * @param   string  model name
	 * @param   array   values to pre-populate the model
	 * @return  Sprig
	 */
	public static function factory($name, array $values = NULL)
	{
		static $models;

		if ( ! isset($models[$name]))
		{
			$class = 'Model_'.$name;

			$models[$name] = new $class;
		}

		// Create a new instance of the model by clone
		$model = clone $models[$name];

		if ($values)
		{
			foreach ($values as $field => $value)
			{
				// Set the initial values
				$model->$field = $value;
			}
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

	// Changed fields
	protected $_changed = array();

	// Related objects
	protected $_related = array();

	// Initialization status
	protected $_init = FALSE;

	/**
	 * Calls the init() method. Sprig constructors are only called once!
	 *
	 * @param   string   model name
	 * @return  void
	 */
	final protected function __construct()
	{
		$this->init();
	}

	/**
	 * Returns the model name.
	 *
	 * @return  string
	 */
	final public function __toString()
	{
		return $this->_model;
	}

	/**
	 * Initialize the fields and add validation rules based on field properties.
	 *
	 * @return  void
	 */
	public function init()
	{
		if ($this->_init)
		{
			// Can only be called once
			return;
		}

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
			if ($field->column === NULL)
			{
				// Create the key based on the field name

				if ($field instanceof Sprig_Field_ForeignKey)
				{
					if ($field instanceof Sprig_Field_HasOne)
					{
						$field->column = $name.'_id';
					}
					else
					{
						$field->column = $this->_model;
					}
				}
				else
				{
					$field->column = $name;
				}
			}

			if ($field instanceof Sprig_Field_ManyToMany)
			{
				if ($field->through === NULL)
				{
					// Create a list of model names
					$table = array((string) $this, (string) Sprig::factory($field->model));

					// Sort the table names alphabetically
					sort($table);

					// Set the pivot table name
					$field->through = implode('_', $table);
				}
			}

			if ($field->label === NULL)
			{
				$field->label = Inflector::humanize($name);
			}

			if ($field->editable)
			{
				if ( ! $field->empty AND ! isset($field->rules['not_empty']))
				{
					// This field must not be empty
					$field->rules['not_empty'] = NULL;
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
		}

		$this->_init = TRUE;
	}

	/**
	 * Returns the primary key of the model, optionally with a table name.
	 *
	 * @param   string  table name, TRUE for the model table
	 * @return  string
	 */
	public function pk($table = FALSE)
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
	public function fk($table = FALSE)
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
	 * Returns the table name of the model.
	 *
	 * @return  string
	 */
	public function table()
	{
		return $this->_table;
	}

	/**
	 * Clones each of the fields and empty the model.
	 *
	 * @return  void
	 */
	public function __clone()
	{
		foreach ($this->_fields as $name => $field)
		{
			$this->_fields[$name] = clone $field;
		}

		$this->_changed = array();
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
			$this->init();
		}

		if (isset($this->_fields[$name]))
		{
			$field = $this->_fields[$name];

			if ($field instanceof Sprig_Field_ForeignKey)
			{
				if ( ! isset($this->_related[$name]))
				{
					// Load the related model
					$model = Sprig::factory($field->model);

					if ($field instanceof Sprig_Field_ManyToMany)
					{
						// Create a joining query
						$query = DB::select()
							->join($field->through)
								->on($model->fk($field->through), '=', $model->pk(TRUE))
							->where($this->fk($field->through), '=', $this->{$this->_primary_key});

						// Load all the related objects
						$this->_related[$name] = $model->load($query, FALSE);
					}
					elseif ($field instanceof Sprig_Field_HasMany)
					{
						// Set the foreign key value
						$model->values(array($field->column => $this->{$this->_primary_key}));

						// Load all the related objects
						$this->_related[$name] = $model->load(NULL, FALSE);
					}
					else
					{
						// Set the primary key value
						$model->values(array($model->pk() => $field->get()));

						// Load the related object
						$this->_related[$name] = $model->load();
					}
				}

				return $this->_related[$name];
			}
			else
			{
				return $field->get();
			}
		}

		throw new Sprig_Exception(':name model does not have a field :field',
			array(':name' => get_class($this), ':field' => $name));
	}

	/**
	 * Set the value of a field.
	 *
	 * @throws  Sprig_Exception  field does not exist
	 * @param   string  field name
	 * @param   mixed   new field value
	 * @return  mixed
	 */
	public function __set($name, $value)
	{
		if ( ! $this->_init)
		{
			$this->init();
		}

		if (isset($this->_fields[$name]))
		{
			$field = $this->_fields[$name];

			if ($field->set($value))
			{
				$this->_changed[$name] = $name;

				if ($field->primary)
				{
					// All object relations are wrong
					$this->_related = array();
				}
				elseif ($field instanceof Sprig_Field_ForeignKey)
				{
					// Any related object will be the wrong
					unset($this->_related[$name]);
				}
			}

			return $this->$name;
		}

		throw new Sprig_Exception(':name model does not have a field :field',
			array(':name' => get_class($this), ':field' => $name));
	}

	/**
	 * Load all of the values in an associative array. Ignores all fields are
	 * not in the model.
	 *
	 * @param   array    field => value pairs
	 * @param   boolean  values are clean (from database)?
	 * @return  $this
	 */
	public function values(array $values, $clean = FALSE)
	{
		// Remove all values which do not have a corresponding field
		$values = array_intersect_key($values, $this->_fields);

		foreach ($values as $field => $value)
		{
			if ($clean === TRUE)
			{
				// Set the field directly
				$this->_fields[$field]->set($value);
			}
			else
			{
				// Set the field using __set()
				$this->$field = $value;
			}
		}

		return $this;
	}

	/**
	 * Get the model data as an associative array.
	 *
	 * @return  array  field => value
	 */
	public function as_array()
	{
		$data = array();

		$fields = array_keys($this->_fields);

		foreach ($fields as $field)
		{
			$data[$field] = $this->$field;
		}

		return $data;
	}

	/**
	 * Get all of the records for this table as an associative array.
	 *
	 * @param   string  array key
	 * @param   string  array value
	 * @return  array   key => value
	 */
	public function select_list($key = 'id', $value = 'name')
	{
		return DB::select($key, $value)
			->from($this->_table)
			->execute($this->_db)
			->as_array($key, $value);
	}

	/**
	 * Test if the model is loaded.
	 *
	 * @return  boolean
	 */
	public function loaded()
	{
		if (is_array($this->_primary_key))
		{
			foreach($this->_primary_key as $field)
			{
				if ( ! $this->$field)
				{
					// Empty primary key value, this record is not loaded
					return FALSE;
				}
			}

			return TRUE;
		}

		$pk = $this->_primary_key;

		return (bool) $this->$pk;
	}

	/**
	 * Get all of the changed fields as an associative array.
	 *
	 * @return  array  field => value
	 */
	public function changed()
	{
		$changed = array();

		foreach ($this->_changed as $field)
		{
			$changed[$field] = $this->$field;
		}

		return $changed;
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
	 * @return  string
	 */
	public function input($field)
	{
		return $this->_fields[$field]->input($field);
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
					$key = form::label($name, $field->label);
				}
				else
				{
					$key = $name;
				}

				$inputs[$key] = $field->input($name);
			}
		}

		return $inputs;
	}

	/**
	 * Load a single record using the current data.
	 *
	 * @return  $this
	 */
	public function load(Database_Query $query = NULL, $limit = 1)
	{
		$changed = $this->changed();

		if ($query === NULL)
		{
			$query = DB::select();
		}

		$query->from($this->_table);

		foreach ($this->_fields as $name => $field)
		{
			if ($field instanceof Sprig_Field_HasMany)
			{
				// Multiple relations cannot be loaded this way
				continue;
			}

			if ($name === $field->column)
			{
				$query->select($name);
			}
			else
			{
				$query->select(array($field->column, $name));
			}

			if (isset($changed[$name]))
			{
				$query->where($field->column, '=', $field->get());
			}
		}

		if ($limit)
		{
			$query->limit($limit);
		}

		if ($limit === 1)
		{
			$result = $query
				->execute($this->_db);

			if (count($result))
			{
				// Load the result
				$this->values($result->current(), TRUE);

				// Nothing has been changed
				$this->_changed = array();
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
		if ($this->_changed)
		{
			// Check the data
			$data = $this->check($this->as_array());

			$values = array();
			foreach ($data as $field => $value)
			{
				// Change the field name to the column name
				$values[$this->_fields[$field]->column] = $value;
			}

			list($id) = DB::insert($this->_table, array_keys($values))
				->values($values)
				->execute($this->_db);

			foreach ($this->_primary_key as $field)
			{
				if ($this->_fields[$field] instanceof Sprig_Field_Auto)
				{
					// Set the auto-increment primary key to the insert id
					$this->_fields[$field]->set($id);

					// There can only be 1 auto-increment column per model
					break;
				}
			}

			// No data has been changed
			$this->_changed = array();
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
		if ($data = $this->changed())
		{
			// Check the data
			$data = $this->check($data);

			$values = array();
			foreach ($data as $field => $value)
			{
				// Change the field name to the column name
				$values[$this->_fields[$field]->column] = $value;
			}

			$query = DB::update($this->_table)
				->set($values);

			if (is_array($this->_primary_key))
			{
				foreach($this->_primary_key as $field)
				{
					$query->where($this->_fields[$field]->column, '=', $this->$field);
				}
			}
			else
			{
				$query->where($this->_fields[$this->_primary_key]->column, '=', $this->{$this->_primary_key});
			}

			if ($query->execute($this->_db))
			{
				// The database is now in sync
				$this->_changed = array();
			}
		}

		return $this;
	}

	public function delete()
	{
		if ($changed = $this->changed())
		{
			$query = DB::delete($this->_table);

			foreach ($changed as $name => $value)
			{
				$query->where($this->_fields[$field]->column, '=', $value);
			}

			if ($query->execute($this->_db))
			{
				return clone $this;
			}
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
			if ($field->editable AND $data->offsetExists($name))
			{
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
		}

		if ( ! $data->check())
		{
			throw new Validate_Exception($data);
		}

		return $data->as_array();
	}

	/**
	 * Initialize the fields. This method will only be called once
	 * by Sprig::init(). All models must define this method!
	 *
	 * @return  void
	 */
	abstract protected function _init();

} // End Sprig