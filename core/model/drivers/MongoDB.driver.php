±<?php
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
    public static $DefaultPrimaryKey = '_id';

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
        if(is_null($key)) $key = self::$DefaultPrimaryKey;
    	$this->PrimaryKey = $key;
    }

    public function getPrimaryKey()
    {
        return $this->PrimaryKey;
    }

    public function escape($value)
    {
    	return $value;
    }

    public function run()
    {
    	$driver_info = $this->Model->__GetDriverInfo('query_material');
    	$QUERY = array(
    		$driver_info['query'],
    		$driver_info['projection']
    	);
            $_START = $this->LogBeforeAction('SELECT', $QUERY);
        $CURSOR = call_user_func_array(array(self::$DB[$this->Server], 'find'), $QUERY);
            $this->LogAfterAction($_START, array());
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
            if(empty($CHANGES))
                return array(
                    'status' => true,
                    'updated' => $data
                );
            $CHANGES['updated_at'] = new MongoTimestamp();
                $_START = $this->LogBeforeAction('UPDATE: '.(string)$data[$this->PrimaryKey].' => ', array('$set' => $CHANGES));
            $STATUS = self::$DB[$this->Server]->update(
                array($this->PrimaryKey => $data[$this->PrimaryKey]),
                array('$set' => $CHANGES)
            );
                $this->LogAfterAction($_START, $STATUS);
            return array(
                'status' => $STATUS,
                'updated' => array_merge($CHANGES, $data)
            );
        }
    }

    public static function created_at()
    {
        return new MongoDate();
    }

    public static function updated_at()
    {
        return new MongoTimestamp();
    }

    public function savenew(&$data)
    {
        $DOCUMENT_ID = new MongoID();
        $data[$this->PrimaryKey] = $DOCUMENT_ID;
        $data['created_at'] = self::created_at();
        $data['updated_at'] = self::updated_at();
            $_START = $this->LogBeforeAction('INSERT', $data);
        $STATUS = self::$DB[$this->Server]->insert($data);
            $this->LogAfterAction($_START, $STATUS);
        return array(
            'pri' => $DOCUMENT_ID,
            'data' => $data
        );
    }

    public function delete(&$ID)
    {
        $QUERY = array($this->PrimaryKey => new MongoId((string)$ID));
            $_START = $this->LogBeforeAction('REMOVE', $QUERY);
        $STATUS = self::$DB[$this->Server]->remove($QUERY, array('justOne' => true));
            $this->LogAfterAction($_START, $STATUS);
        if((float)$STATUS['ok'] === (float)1) return true;
        return false;
    }

    //============================================================================//
    // Log Method                                                                 //
    //============================================================================//

    private function LogBeforeAction($action_name, $action)
    {
        if($GLOBALS['ENV'] != 'PRO')
        {
            $LOG = fopen(DIR_LOG."/development.log", 'a');
            fwrite($LOG, "\033[36mSTART\033[0m: ".date('H:i:s')."\t".$action_name.": ".trim(var_export($action, true))."\n");
            fclose($LOG);
        }
        return microtime(true);
    }

    private function LogAfterAction(&$_START, $STATUS)
    {
        if($GLOBALS['ENV'] != 'PRO')
        {
            $_END = microtime(true);
            $LOG = fopen(DIR_LOG."/development.log", 'a');
            if(isset($STATUS['err']) && !is_null($STATUS['err']))
            {
                fwrite($LOG, "\033[35mERROR\033[0m: ".date('H:i:s')."\tMSG:\033[0m [".$STATUS['err']."]\n");
                trigger_error("[MongoDB ERROR] => ".$STATUS['err'], E_USER_WARNING);
            }
            fwrite($LOG, "\033[35mEND\033[0m: ".date('H:i:s')."\tTime\033[0m [".round($_END - $_START, 5)."]\n");
            fclose($LOG);
        }
    }

    //============================================================================//
    // Create Table Methods                                                       //
    //============================================================================//

    public static function DropTable($name)
    {
        $Server = DB_SERVER;
        $CONNECTION_STRING = 'mongodb://';
        if(DB_USERNAME != '') $CONNECTION_STRING .= DB_USERNAME;
        if(DB_PASSWORD != '') $CONNECTION_STRING .= ':'.DB_PASSWORD;
        if(DB_USERNAME != '') $CONNECTION_STRING .= '@';
        $CONNECTION_STRING .= $Server;
        $m = new MongoClient($CONNECTION_STRING);

        $DB = DB_DATABASE;
        $db = $m->$DB;
        $c = $db->$name;
        $c->drop();
    }

    public static function CreateTable($name, $fields)
    {
        return true;
    }

    //============================================================================//
    // Query Builder Methods                                                      //
    //============================================================================//
    
    public function search($where = array(), $select = array())
    {
        $FIELDS = array();
        if(!empty($select))
            $FIELDS = array_fill_keys($select, true);
        $this->find($where, $FIELDS);
    }

    public function findOne($where = array())
    {
        $this->findOne($where);
    }

    public function projection($projections = array())
    {
    	$driver_info = &$this->Model->__GetDriverInfo('query_material');
    	if(is_array($projections))
    		$driver_info['projection'] = $projections;
    }

    public function find($matches = array())
    {
    	$driver_info = &$this->Model->__GetDriverInfo('query_material');
    	if(is_array($matches))
    		$driver_info['query'] = $matches;
    }
}
?>