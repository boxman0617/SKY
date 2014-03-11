<?php
abstract class Migration
{
	private static $_connection = null;

	final public function __construct($db_conn)
	{
		self::$_connection = $db_conn;
	}

	final public static function GetDB()
	{
		return self::$_connection;
	}

	protected function DropTable($table)
	{

	}

	abstract public function Up();
	abstract public function Down();
}

abstract class MigrateTable
{
	protected $_column_types = array(
		'bit' => array(
			'optional' => array('length')
		),
		'tinyint' => array(
			'optional' => array('length', 'unsigned', 'zerofill')
		),
		'smallint' => array(
			'optional' => array('length', 'unsigned', 'zerofill')
		),
		'mediumint' => array(
			'optional' => array('length', 'unsigned', 'zerofill')
		),
		'int' => array(
			'optional' => array('length', 'unsigned', 'zerofill')
		),
		'bigint' => array(
			'optional' => array('length', 'unsigned', 'zerofill')
		),
		'real' => array(
			'optional' => array('length_decimals', 'unsigned', 'zerofill')
		),
		'double' => array(
			'optional' => array('length_decimals', 'unsigned', 'zerofill')
		),
		'float' => array(
			'optional' => array('length_decimals', 'unsigned', 'zerofill')
		),
		'decimal' => array(
			'optional' => array('length', 'decimals', 'unsigned', 'zerofill')
		),
		'numeric' => array(
			'optional' => array('length', 'decimals', 'unsigned', 'zerofill')
		),
		'date' => true,
		'time' => true,
		'timestamp' => true,
		'datetime' => true,
		'year' => true,
		'char' => array(
			'optional' => array('length', 'character_set', 'collate')
		),
		'varchar' => array(
			'optional' => array('length', 'character_set', 'collate')
		),
		'binary' => array(
			'optional' => array('length')
		),
		'varbinary' => array(
			'required' => array('length')
		),
		'tinyblob' => true,
		'blob' => true,
		'mediumblob' => true,
		'longblob' => true,
		'tinytext' => array(
			'optional' => array('binary', 'character_set', 'collate')
		),
		'text' => array(
			'optional' => array('binary', 'character_set', 'collate')
		),
		'mediumtext' => array(
			'optional' => array('binary', 'character_set', 'collate')
		),
		'longtext' => array(
			'optional' => array('binary', 'character_set', 'collate')
		),
		'enum' => array(
			'required' => array('values'),
			'optional' => array('character_set', 'collate')
		),
		'set' => array(
			'required' => array('values'),
			'optional' => array('character_set', 'collate')
		)
	);

	protected function ProcessOptionRow_format($value, $options = array())
	{
		if(!in_array($value, array('DEFAULT', 'DYNAMIC', 'FIXED', 'COMPRESSED', 'REDUNDANT', 'COMPACT')))
			throw new Exception('Migration failed. ROW_FORMAT requires value to be either {DEFAULT|DYNAMIC|FIXED|COMPRESSED|REDUNDANT|COMPACT}. ['.$value.'] was given.');
		return 'ROW_FORMAT = '.$value;
	}
	
	protected function ProcessOptionInsert_method($value, $options = array())
	{
		if(!in_array($value, array('NO', 'FIRST', 'LAST')))
			throw new Exception('Migration failed. INDEX_METHOD requires value to be either NO, FIRST, or LAST. ['.$value.'] was given.');
		return 'INSERT_METHOD = '.$value;
	}

	protected function ProcessOptionPassword($value, $options = array())
	{
		return $this->StringOptions('PASSWORD', $value);
	}

	protected function ProcessOptionIndex_directory($value, $options = array())
	{
		return $this->StringOptions('INDEX DIRECTORY', $value);
	}

	protected function ProcessOptionComment($value, $options = array())
	{
		return $this->StringOptions('COMMENT', $value);
	}

	protected function ProcessOptionConnection($value, $options = array())
	{
		return $this->StringOptions('CONNECTION', $value);
	}

	protected function ProcessOptionData_directory($valye, $options = array())
	{
		return $this->StringOptions('DATA DIRECTORY', $value);
	}

	private function StringOptions($keyword, $string)
	{
		return $keyword.' = "'.$string.'"';
	}

	protected function ProcessOptionChecksum($value, $options = array())
	{
		if(!in_array($value, array(0, 1)))
			throw new Exception('Migration failed. CHECKSUM requires value to be either 1 or 0. ['.$value.'] was given.');
		return 'CHECKSUM = '.$value;
	}

  	protected function ProcessOptionCharacter_set($value, $options = array())
  	{
  		$option = '';
  		if(in_array('default', $options))
  			$option .= 'DEFAULT ';
  		$option .= 'CHARACTER SET = '.$value;
  		return $option;
  	}

	protected function ProcessValues($values = array())
	{
		return "('".implode("','", $values)."')";
	}

	protected function ProcessLength($size)
	{
		return '('.$size.')';
	}

	protected function ProcessLength_decimals($size)
	{
		if(is_array($size))
		{
			return '('.$size[0].', '.$size[1].')';
		}
		return '('.$size.')';
	}

	protected function ProcessBinary()
	{
		return ' BINARY';
	}

	protected function ProcessCharacter_set($set)
	{
		return ' CHARACTER SET '.$set;
	}

	protected function ProcessCollate($set)
	{
		return ' COLLARE '.$set;
	}

	protected function ProcessUnsigned()
	{
		return ' UNSIGNED';
	}

	protected function ProcessZerofill()
	{
		return ' ZEROFILL';
	}
}

class CreateTable extends MigrateTable
{
	private $_table_name;
	private $_columns = array();
	private $_indexes = array();
	private $_table_options = array(
		'engine' => array(
			'value' => 'InnoDB',
			'options' => array()
		)
	);

	public function __construct($table_name)
	{
		$this->_table_name = $table_name;
	}

	public function AddColumn($name, $type, $options = array())
	{
		$this->_columns[$name] = array(
			'type' => $type,
			'options' => $options
		);
	}

	public function AddIndex($columns = array(), $type = 'index')
	{
		$this->_indexes[] = array(
			'columns' => $columns,
			'type' => $type
		);
	}

	public function AddOption($name, $value, $options = array())
	{
		$this->_table_options[$name] = array(
			'value' => $value,
			'options' => $options
		);
	}

	public function Create()
	{
		$query = 'CREATE TABLE `'.$this->_table_name.'` ';
		$query .= '(`id` INT(11) NOT NULL AUTO_INCREMENT, ';
		foreach($this->_columns as $name => $column)
		{
			$query .= '`'.$name.'` '.strtoupper($column['type']);
			
			if(array_key_exists('required', $this->_column_types[$column['type']]))
			{
				foreach($this->_column_types[$column['type']]['required'] as $required)
				{
					if(!array_key_exists($required, $column['options']))
						throw new Exception('Migration failed due to unfullfilled required field ['.$required.'] for column ['.$name.']');
					
					$method = 'Process'.ucfirst($required);
					if(method_exists($this, $method))
						$query .= call_user_func(array($this, $method), $column['options'][$required]);
				}
			}

			if(array_key_exists('optional', $this->_column_types[$column['type']]))
			{
				foreach($this->_column_types[$column['type']]['optional'] as $optional)
				{
					if(array_key_exists($optional, $column['options']))
					{
						$method = 'Process'.ucfirst($optional);
						if(method_exists($this, $method))
							$query .= call_user_func(array($this, $method), $column['options'][$optional]);
					}
				}
			}

			if(array_key_exists('null', $column['options']))
			{
				if($column['options']['null'] === false)
					$query .= ' NOT NULL';
				else
					$query .= ' NULL';
			}

			if(array_key_exists('default', $column['options']))
			{
				$query .= ' DEFAULT ';
				if(is_string($column['options']['default']))
					$query .= '"'.$column['options']['default'].'"';
				else
					$query .= $column['options']['default'];
			}

			if(array_key_exists('auto_increment', $column['options']))
			{
				$query .= ' AUTO_INCREMENT';
			}

			if(array_key_exists('comment', $column['options']))
			{
				$query .= ' COMMENT "'.$column['options']['comment'].'"';
			}

			if(array_key_exists('column_format', $column['options']))
			{
				if(!in_array(strtoupper($column['options']['column_format']), array('FIXED', 'DYNAMIC', 'DEFAULT')))
					throw new Exception('Migration failed due to column formating being incorrect ['.$name.'] {FIXED, DYNAMIC, DEFAULT}');
				$query .= ' COLUMN_FORMAT '.strtoupper($column['options']['column_format']);
			}

			if(array_key_exists('storage', $column['options']))
			{
				if(!in_array(strtoupper($column['options']['storage']), array('DISK', 'MEMORY', 'DEFAULT')))
					throw new Exception('Migration failed due to column storage being incorrect ['.$name.'] {DISK, MEMORY, DEFAULT}');
				$query .= ' STORAGE '.strtoupper($column['options']['storage']);
			}

			$query .= ', ';
		}

		$query .= '`created_at` DATETIME NOT NULL, `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP, PRIMARY KEY (`id`)';
		foreach($this->_indexes as $index)
		{
			$query .= ', '.strtoupper($index['type']).' INDX_'.implode('_', $index['columns']).' (`'.implode('`, `', $index['columns']).'`)';
		}
		$query .= ') ';

		foreach($this->_table_options as $name => $option)
		{
			$method = 'ProcessOption'.ucfirst($name);
			if(method_exists($this, $method))
				$query .= call_user_func_array(array($this, $method), array($option['value'], $option['options']));
			else {
				$query .= strtoupper($name).' = '.$option['value'];
			}
			$query .= ', ';
		}

		$query = substr($query, 0, -2);

		$db = Migration::GetDB();
		$result = $db->query($query);
		if($result === false)
			throw new Exception('Unable to create new table! Unexpected error.');
	}
}

class AlterTable extends MigrateTable
{
	private $_table_name;

	public function __construct($table_name)
	{
		$this->_table_name = $table_name;
	}

	public function RenameTable($table_name)
	{

	}

	public function RenameColumn($old_name, $new_name)
	{

	}
}

class Table
{
	public static function Create($table_name)
	{
		return new CreateTable($table_name);
	}

	public static function Open($table_name)
	{
		return new AlterTable($table_name);
	}
}
?>