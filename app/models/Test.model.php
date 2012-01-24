<?php
//Example of Data driven Model
class Test extends Model
{
    protected $model_type = M_TYPE_DATA;
    protected $table_name = 'test';
    protected $data_info = array(
        0 => array(
            'id' => 0,
            'name' => 'boom'
        ),
        1 => array(
            'id' => 1,
            'name' => 'haha'
        )
    );
    
    public function __construct($id = false)
    {
        self::$table_schema['test'] = array(
            'id' => array(
                "Type" => 'int(11)',
                "Null" => 'NO',
                "Key" => 'PRI',
                "Default" => '',
                "Extra" => 'auto_increment'
            ),
            'name' => array(
                "Type" => 'varchar(255)',
                "Null" => 'NO',
                "Key" => '',
                "Default" => '',
                "Extra" => ''
            )
        );
        parent::__construct($id);
    }
}
?>