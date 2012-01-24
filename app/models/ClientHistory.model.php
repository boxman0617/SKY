<?php
class ClientHistory extends Model
{
    //Example of validation array
    public $validate = array(
        'client_id' => array(
            'required' => true,
            'must_be' => 'integer'
        ),
        'adminuser_id' => array(
            'required' => true,
            'must_be' => 'integer'
        ),
        'previous_status' => array(
            'required' => true
        ),
        'new_status' => array(
            'required' => true
        )
    );
    
    //Example of output formatting array
    public $output_format = array(
        'time_changed' => array(
            'custom' => 'FormatTime'
        ),
        'client_id' => 'Client ID: %d'
    );
    
    //Example of custom formatting method
    public function FormatTime($time)
    {
        return date("F j, Y @ g:iA", strtotime($time));
    }
}
?>