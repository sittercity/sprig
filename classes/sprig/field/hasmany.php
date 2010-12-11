<?php defined('SYSPATH') or die('No direct script access.');
/**
 * Sprig "has many" relationship field.
 *
 * @package    Sprig
 * @author     Woody Gilk
 * @copyright  (c) 2009 Woody Gilk
 * @license    MIT
 */
class Sprig_Field_HasMany extends Sprig_Field_ForeignKey {

	public $empty = TRUE;

	public $default = array();

	public $editable = FALSE;

	public $columns; // array(fk => pk)
	
	public function value($value)
	{
		if (empty($value) AND $this->empty)
		{
			return array();
		}
		elseif (is_object($value))
		{
			// Assume this is a Database_Result object

			$result = array();
			foreach ($value as $model)
			{
				$row = array();
				if(is_string($model->pk()))
				{
					// Single PK
					$row[$model->pk()] = $model->{$model->pk()};
				}
				else
				{
					// Composite PK
					foreach ($model->pk() as $pk)
					{
						$row[$pk] = $model->$pk;
					}
				}
				$result[] = $row;
			}
			$value = $result;
		}
		else
		{
			// Value must always be an array
			foreach($value as $idx1 => $arr)
			{
				if(is_array($arr))
				{
					foreach($arr as $idx2 => $val)
					{
						if(is_numeric($val))
						{
							$value[$idx1][$idx2] = (int)$val; // Assume this is an integer type key of some kind
						}
					}
				}
			}
		}

		return $value;
	}

	public function verbose($value)
	{
		$value = $this->value($value);
		
		// $this->value($value) will return array
		// The array can contain another array
		foreach($value as $i => $row) {
			if(is_array($row))
			{
				// implode each array
				$value[$i] = implode('-', $row);
			}
		}
		
		return implode(', ', $value);
	}

	public function input($name, $value, array $attr = NULL)
	{
		$model = Sprig::factory($this->model);

		// All available options
		if(isset($this->foreignkey_valuefield))
			$options = $model->select_list($this->foreignkey_valuefield);
		else
			$options = $model->select_list($model->pk());

		// Convert the selected options
		if(isset($this->foreignkey_valuefield))
		{
			$value_new = array();
			foreach($this->value($value) as $array)
			{
				if(is_array($array))
				{
					// $array can be one or more primary keys for $model
					// We'll use only the key "foreignkey_valuefield"
					$value_new[$array[$this->foreignkey_valuefield]] = $array;
				}
				elseif(is_object($array))
				{
					$value_new[$array->{$this->foreignkey_valuefield}] = $array;
				}
				else
				{
					throw new Kohana_Exception('Unknown.');
				}
			}
			$value = $value_new;
		}
		else
		{
			$value = $this->value($value);
		}
		
		$inputs = array();
		foreach ($options as $id => $label)
		{
			$inputs[] = '<label>'.Form::checkbox("{$name}[]", $id, isset($value[$id])).' '.$label.'</label>';
		}

		// Hidden input is added to force $_POST to contain a value for
		// this field, even when nothing is selected.

		return Form::hidden($name, '').implode('<br/>', $inputs);
	}

} // End Sprig_Field_ManyToMany
