<?php
/**
 * iDriver interface
 *
 * This is a template for Model back-end drivers.
 *
 * LICENSE:
 *
 * This file may not be redistributed in whole or significant part, or
 * used on a web site without licensing of the enclosed code, and
 * software features.
 *
 * @author      Alan Tirado <root@deeplogik.com>
 * @copyright   2013 DeepLogik, All Rights Reserved
 * @license     http://www.codethesky.com/license
 * @link        http://www.codethesky.com/docs/idriverinterface
 * @package     Sky.Core
 */

interface iDriver {
    public function __construct($db);

    /**
     * This method allows the Driver to create a property
     * in the Model class.
     * @param  Model $model Reference object of Model class
     * @return void
     */
    public function buildModelInfo(&$model);

    /**
     * Sets the local "table" name property
     * @param String $name Name of "table"
     */
    public function setTableName($name);

    /**
     * Sets the local primary key, this allows the Model
     * class and Driver to update "records"
     * @param String $key Passed by reference name of primary key
     */
    public function setPrimaryKey(&$key);
    
    /**
     * It escapes any characters that might be harmful
     * to the database
     * @param  String $value Variable that may need escaping
     * @return String        Escaped string
     */
    public function escape($value);

    /**
     * Using the ::__GetDriverInfo() method, use any data
     * stored on the Model class to create the required
     * "query" for querying the database.
     * @return Array    An array of the results must be returned 
     *                  so the Model class can use it in it's
     *                  Model::_iterator_data property. The array
     *                  must be a 2-dimensional array consisting
     *                  of numerical [0,1,2,...] indexes and
     *                  an associative array of field names and 
     *                  their values.
     */
    public function run();

    /**
     * This allows a current record to be updated
     * @param  Array $unaltered 	Passed by reference array of pre-altered
     *                            	fields. It allows the Driver to figure out
     *                          	what actually needs to be altered. This array
     *                          	is the ENTIRE unaltered array from the Model
     *                          	class. It requires the $position argument to
     *                          	see if any data has been altered or not.
     * @param  Array 	$data      	Passed by reference array of altered data. This
     *                             	does not require the $position argument.
     * @param  Integer 	$position  	Current position in the ::_iterator_data of the
     *                              Model class.
     * @return Array            	This method must return an array consisting of two
     *                              keys: status and updated. These two keys are required
     *                              by the Model class. Status should be filled with a
     *                              boolean value representing the success or failure of 
     *                              the update query. Updated should be updated data. 
     */
    public function update(&$unaltered, &$data, $position);

    /**
     * This allows a new record to be saved
     * @param  Array $data Array made up of fields and values to be
     *                     Saved to the database
     * @return Array       This method must return an array consisting of two
     *                     keys: pri and data. These two keys are required
     *                     by the Model class. Pri should be filled with the
     *                     primary key value. Data should be the full record including
     *                     primary key, created_at, and updated_at values.
     */
    public function savenew(&$data);

    public function delete(&$ID);

    // #Needed private methods
    // private function LogBeforeAction($action_name, $action);
    // private function LogAfterAction(&$_START, $STATUS);
}
?>