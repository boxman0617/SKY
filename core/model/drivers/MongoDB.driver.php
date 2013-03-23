<?php
import(SKYCORE_CORE_MODEL."/Driver.interface.php");

class MongoDBDriver implements iDriver
{
	private $Client;
	private $Database;
	private $Collection;
	private $PrimaryKey;
	private $Server;
	private static $DB;
	private $Model;

	public function __construct($db)
	{
		$this->Server = $db['DB_SERVER'];
		$CONNECTION_STRING = 'mongodb://';
		if($db['DB_USERNAME'] != '') $CONNECTION_STRING .= $db['DB_USERNAME'];
		if($db['DB_PASSWORD'] != '') $CONNECTION_STRING .= ':'.$db['DB_PASSWORD'];
		if($db['DB_USERNAME'] != '') $CONNECTION_STRING .= '@';
		$CONNECTION_STRING .= $this->Server;
		$this->Client = new MongoClient($CONNECTION_STRING);
		$this->Database = $this->Client->$db['DB_DATABASE'];
	}

	public function buildModelInfo(&$model)
	{
		$this->Model = $model;
		$driver_info = &$this->Model->__GetDriverInfo('query_material');
		$driver_info = array(
			'query' 	=> array(),
	        'projection'=> array()
		);
	}

    public function setTableName($name)
    {
    	$this->Collection = $name;
    	self::$DB[$this->Server] = $this->Database->$name;
    }

    public function setPrimaryKey(&$key)
    {
        if(is_null($key)) $key = '_id';
    	$this->PrimaryKey = $key;
    }

    public function getPrimaryKey()
    {
        return $this->PrimaryKey;
    }

    public function escape($value)
    {
    	
    }

    public function run()
    {
    	$driver_info = $this->Model->__GetDriverInfo('query_material');
    	$QUERY = array(
    		$driver_info['query'],
    		$driver_info['projection']
    	);
        if($GLOBALS['ENV'] != 'PRO')
        {
            $LOG = fopen(DIR_LOG."/development.log", 'a');
            fwrite($LOG, "\033[36mSTART\033[0m: ".date('H:i:s')."\t".trim(var_export($QUERY, true))."\n");
            fclose($LOG);
            $_START = microtime(true);
        }
        $CURSOR = call_user_func_array(array(self::$DB[$this->Server], 'find'), $QUERY);
        if($GLOBALS['ENV'] != 'PRO')
        {
            $_END = microtime(true);
            $LOG = fopen(DIR_LOG."/development.log", 'a');
            fwrite($LOG, "\033[35mEND\033[0m: ".date('H:i:s')."\t\033[1;36mResults\033[0m [".count($CURSOR)."] \033[1;32mTime\033[0m [".round($_END - $_START, 5)."]\n");
            fclose($LOG);
        }
        $RETURN = array();
        foreach($CURSOR as $C)
        {
            $C['created_at'] = date('Y-m-d H:i:s', $C['created_at']->sec);
            $C['updated_at'] = date('Y-m-d H:i:s', $C['updated_at']->sec);
        	$RETURN[] = $C;
        }
        return $RETURN;
    }

    public function update(&$unaltered, &$data, $position)
    {
        if(isset($unaltered[$position]))
        {
            $CHANGES = array_diff($data, $unaltered[$position]);
            $CHANGES['updated_at'] = new MongoTimestamp();
            $STATUS = self::$DB[$this->Server]->update(
                array($this->PrimaryKey => $data[$this->PrimaryKey]),
                array('$set' => $CHANGES)
            );
            return array(
                'status' => $STATUS,
                'updated' => array_merge($CHANGES, $data)
            );
        }
    }

    public function savenew(&$data)
    {
        $DOCUMENT_ID = new MongoID();
        $data[$this->PrimaryKey] = $DOCUMENT_ID;
        $data['created_at'] = new MongoDate();
        $data['updated_at'] = new MongoTimestamp();
        self::$DB[$this->Server]->insert($data);
        return array(
            'pri' => $DOCUMENT_ID,
            'data' => $data
        );
    }



    //============================================================================//
    // Query Builder Methods                                                      //
    //============================================================================//
    
    public function projection($projections)
    {
    	$driver_info = &$this->Model->__GetDriverInfo('query_material');
    	if(is_array($projections))
    		$driver_info['projection'] = $projections;
    }

    public function find($matches)
    {
    	$driver_info = &$this->Model->__GetDriverInfo('query_material');
    	if(is_array($matches))
    		$driver_info['query'] = $matches;
    }
}
?>