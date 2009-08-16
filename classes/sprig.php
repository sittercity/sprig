<?php defined('SYSPATH') or die('No direct script access.');

abstract class Sprig {

	protected $_db = 'default';

	protected $_table;

	protected $_primary_key = array();

	protected $_fields = array();

	protected $_relations = array();

	protected $_changed = array();

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

	protected function __construct()
	{
		$this->init();
	}

	public function init()
	{
		foreach ($this->_fields as $name => $field)
		{
			if ($field->column === NULL)
			{
				if ($field instanceof Sprig_Field_ForeignKey)
				{
					$field->column = $name.'_id';
				}
				else
				{
					$field->column = $name;
				}
			}

			if ($field->label === NULL)
			{
				$field->label = Inflector::humanize($name);
			}

			if ($field->primary === TRUE)
			{
				$this->_primary_key[$name] = $name;
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

				if ($field->unique)
				{
					// This field must be checked for uniqueness
					$field->callbacks[] = array($this, 'unique_value');
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
	}

	public function __clone()
	{
		foreach ($this->_fields as $name => $field)
		{
			$this->_fields[$name] = clone $field;
		}

		$this->_changed = array();
	}

	public function __get($field)
	{
		if (isset($this->_fields[$field]))
		{
			return $this->_fields[$field]->get();
		}

		throw new Sprig_Exception(':name model does not have a field :field',
			array(':name' => get_class($this), ':field' => $field));
	}

	public function __set($field, $value)
	{
		if (isset($this->_fields[$field]))
		{
			if ($this->_fields[$field]->set($value))
			{
				$this->_changed[$field] = $field;
			}

			return $this->$field;
		}

		throw new Sprig_Exception(':name model does not have a field :field',
			array(':name' => get_class($this), ':field' => $field));
	}

	public function values(array $values)
	{
		foreach ($values as $field => $value)
		{
			$this->$field = $value;
		}

		return $this;
	}

	public function as_array()
	{
		$data = array();
		foreach ($this->_fields as $name => $field)
		{
			$data[$name] = $field->get();
		}
		return $data;
	}

	public function select_list($key = 'id', $value = 'name')
	{
		return DB::select($key, $value)
			->from($this->_table)
			->execute($this->_db)
			->as_array($key, $value);
	}

	public function loaded()
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

	public function changed()
	{
		$changed = array();

		foreach ($this->_changed as $field)
		{
			$changed[$field] = $this->$field;
		}

		return $changed;
	}

	public function field($name)
	{
		return $this->_fields[$name];
	}

	public function fields()
	{
		return $this->_fields;
	}

	public function inputs($labels = FALSE)
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

	public function load()
	{
		if ($this->_changed)
		{
			$query = DB::select("{$this->_table}.*")->from($this->_table)->limit(1);

			foreach ($this->changed() as $field => $value)
			{
				$query->where($this->_fields[$field]->column, '=', $value);
			}

			$result = $query->execute($this->_db);

			if (count($result))
			{
				$result = $result->current();

				foreach ($this->_fields as $name => $field)
				{
					if (isset($result[$field->column]))
					{
						$field->set($result[$field->column]);
					}
				}

				// No data has been changed
				$this->_changed = array();
			}
		}

		return $this;
	}

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
				if ($this->_fields[$field] instanceof Spig_Field_Auto)
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

			foreach ($this->_primary_key as $field)
			{
				$query->where($this->_fields[$field]->column, '=', $this->$field);
			}

			if ($query->execute($this->_db))
			{
				// The database is now in sync
				$this->_changed = array();
			}
		}

		return $this;
	}

	public function check(array $data)
	{
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

	public function all(array $conditions = NULL)
	{
		$query = DB::select("{$this->_table}.*")
			->from($this->_table);

		if ($changed = $this->changed())
		{
			foreach ($changed as $field => $value)
			{
				// Apply each changed value as a WHERE clause
				$query->where($this->_fields[$field]->column, '=', $value);
			}
		}

		if ($conditions)
		{
			foreach ($conditions as $field => $value)
			{
				// Apply each condition as a WHERE clause
				$query->where($this->_fields[$field]->column, '=', $value);
			}
		}

		$result = $query->execute();

		$all = array();

		if (count($result))
		{
			foreach ($result as $row)
			{
				$values = array();

				foreach ($this->_fields as $name => $field)
				{
					if (isset($row[$field->column]))
					{
						$values[$name] = $row[$field->column];
					}
				}

				$all[] = $model = clone $this;

				$model->values($values);

				unset($model);
			}
		}

		return $all;
	}

} // End Sprig