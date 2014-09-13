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
		if(self::GetDB()->query('DROP TABLE `'.$table.'`') === false)
			throw new Exception('Unable to drop table! Unexpected error.');

		$model = SkyDefines::Call('DIR_APP_MODELS').'/'.Sky::UnderscoreToUpper($table).'.model.php';
		if(is_file($model))
			@unlink($model);
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

	// #############################################################################
	protected function CreateAddColumn($query, $name, $column)
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
		return $query;
	}
	// #############################################################################

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

	protected function ProcessDecimals($size)
	{
		if(!is_array($size))
			throw new Exception('Option decimals expects an array.');
		return '('.$size[0].', '.$size[1].')';
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
		$query = 'CREATE TABLE IF NOT EXISTS `'.$this->_table_name.'` ';
		$query .= '(`id` INT(11) NOT NULL AUTO_INCREMENT, ';
		$columns = '';
		foreach($this->_columns as $name => $column)
		{
			$columns .= sprintf("// %-20s %s\n", $name, $column['type']);
			$query = $this->CreateAddColumn($query, $name, $column);
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

		$org_name = $this->_table_name;
        // Building Model
        if(strpos($this->_table_name, '_') !== false)
        {
            $MODEL_NAME = ucwords(str_replace('_', ' ', $this->_table_name));
            $this->_table_name = str_replace(' ', '', $MODEL_NAME);
        } else {
            $this->_table_name = ucfirst($this->_table_name);
        }
        $class = "<?php
// ####
// [".$org_name."]
".$columns."// ##

class ".$this->_table_name." extends Model
{

}

";
        $f = fopen(SkyDefines::Call('DIR_APP_MODELS')."/".$this->_table_name.".model.php", "w");
        fwrite($f, $class);
        fclose($f);
	}
}

class AlterTable extends MigrateTable
{
	private $_table_name;
	private $_columns = array();
	private $_rename_table = false;

	const ADD = 'ADD';
	const DROP = 'DROP';
	const CHANGE = 'CHANGE';
	const ALTER = 'ALTER';
	const MODIFY = 'MODIFY';

	public function __construct($table_name)
	{
		$this->_table_name = $table_name;
	}

	public function RenameTable($table_name)
	{
		$this->_rename_table = $table_name;
	}

	public function AddIndex($columns = array(), $type = 'index')
	{
		$this->_indexes[] = array(
			'columns' => $columns,
			'type' => $type,
			'action' => self::ADD
		);
	}

	public function DropIndex($name)
	{
		$this->_indexes[] = array(
			'name' => $name,
			'action' => self::DROP
		);
	}

	public function AddColumn($name, $type, $options = array())
	{
		$this->_columns[$name] = array(
			'type' => $type,
			'options' => $options,
			'action' => self::ADD
		);
	}

	public function DropColumn($name)
	{
		$this->_columns[$name] = array(
			'action' => self::DROP
		);
	}

	public function AlterColumn($name, $options = array())
	{
		if((array_key_exists('set_default', $options) || array_key_exists('drop_default', $options)) === false)
			throw new Exception('::AlterColumn() options should have {set_defaulr | drop_default}');
		$this->_columns[$name] = array(
			'action' => self::ALTER,
			'options' => $options
		);
	}

	public function ChangeColumn($old_col_name, $new_col_name, $type, $options = array())
	{
		$this->_columns[$old_col_name] = array(
			'action' => self::CHANGE,
			'type' => $type,
			'new_col_name' => $new_col_name,
			'options' => $options
		);
	}

	public function ModifyColumn($name, $options = array())
	{
		$this->_columns[$name] = array(
			'action' => self::MODIFY,
			'options' => $options
		);
	}

	public function Alter()
	{
		$query = 'ALTER TABLE `'.$this->_table_name.'` ';

		$query = $this->ProcessingColumns($query);

		// @ToDo: Process other alter statements

		$db = Migration::GetDB();
		$result = $db->query($query);
		if($result === false)
			throw new Exception('Unable to alter table! Unexpected error.');
	}

	private function ProcessingColumns($query)
	{
		foreach($this->_columns as $name => $column)
		{
			$query .= call_user_func_array(
				array($this, '_'.$column['action'].'Column'),
				array($name, $column)
			);

			$pos = false;
			if(array_key_exists('options', $column))
			{
				if(array_key_exists('before', $column['options']))
					$pos = 'BEFORE';
				elseif(array_key_exists('after', $column['options']))
					$pos = 'AFTER';
			}
			if($pos !== false)
			{
				$query = substr($query, 0, -2);
				$query .= ' '.$pos.' `'.$column['options'][strtolower($pos)].', ';
			}
		}
		return substr($query, 0, -2);
	}

	private function _ADDColumn($name, $options)
	{
		$column = self::ADD.' COLUMN ';
		return $this->CreateAddColumn($column, $name, $options);
	}

	private function _DROPColumn($name, $options)
	{
		return self::DROP.' COLUMN `'.$name.'`, ';
	}

	private function _ALTERColumn($name, $options)
	{
		$column = self::ALTER.' COLUMN `'.$name.'` ';
		if(array_key_exists('set_default', $options))
		{
			$column .= 'SET_DEFAULT ';
			if(is_string($options['set_default']))
				$column /= '"'.$options['set_default'].'"';
			else
				$column .= $options['set_default'];
		} elseif(array_key_exists('drop_default', $options)) {
			$column .= 'DROP_DEFAULT';
		}
		return $column.', ';
	}

	private function _CHANGEColumn($name, $options)
	{
		$column = self::CHANGE.' COLUMN `'.$name.'` ';
		return $this->CreateAddColumn($column, $options['new_col_name'], $options);
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
