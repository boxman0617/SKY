<?php
class SkyPayPal
{
    public static $Settings = array(
        ':ENV' => array(
            'APIVersion'            => '85.0',
            'APIUsername'           => null,
            'APIPassword'           => null,
            'APISignature'          => null,
            'DeviceID'              => null,
            'ApplicationID'         => null,
            'DeveloperEmailAccount' => null,
            'ButtonSource'          => 'SkyPayPal_PHPClass'
        ),
        ':endpoint_url' => array(
            'DEV'   => 'https://api-3t.sandbox.paypal.com/nvp',
            'TEST'  => 'https://api-3t.sandbox.paypal.com/nvp',
            'PRO'   => 'https://api-3t.paypal.com/nvp'
        )
    );
    
    private $_Response = null;
    private $_NotValid = array();
    
    private function CURLRequest($REQUEST_ARRAY)
    {
        $curl = curl_init();
                curl_setopt($curl, CURLOPT_VERBOSE, 1);
                curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
                curl_setopt($curl, CURLOPT_TIMEOUT, 30);
                curl_setopt($curl, CURLOPT_URL, self::$Settings[':endpoint_url'][$GLOBALS['ENV']]);
                curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($REQUEST_ARRAY));
        
        $RESPONSE = curl_exec($curl);
        $RESPONSE_ARRAY = array();
        parse_str($RESPONSE, $RESPONSE_ARRAY);
        curl_close($curl);
        return $RESPONSE_ARRAY;
    }
    
    public function is_successful()
    {
        return ($this->_Response['ACK'] == 'Success');
    }
    
    public function GetTransactionId()
    {
        return $this->_Response['TRANSACTIONID'];
    }
    
    public function Result()
    {
        return $this->_Response;
    }
    
    public function GetNonValid()
    {
        return $this->_NotValid;
    }
    
    private function CreateRequest()
    {
        $PARAMS = func_get_args();
        $NUMARGS = func_num_args();
        $REQUEST = array(
            'USER'          => self::$Settings[':ENV']['APIUsername'],
            'PWD'           => self::$Settings[':ENV']['APIPassword'],
            'VERSION'       => self::$Settings[':ENV']['APIVersion'],
            'BUTTONSOURCE'  => self::$Settings[':ENV']['ButtonSource'],
            'SIGNATURE'     => self::$Settings[':ENV']['APISignature']
        );
        for ($i = 0; $i < $NUMARGS; $i++)
            $REQUEST = array_merge($REQUEST, $PARAMS[$i]);
        return $REQUEST;
    }
    
    public function GetTransactionDetails($TransactionID)
    {
        $REQUEST = array(
            'METHOD' => 'GetTransactionDetails',
            'TRANSACTIONID' => $TransactionID
        );
        $REQUEST = $this->CreateRequest($REQUEST);
        $RESPONSE_ARRAY = $this->CURLRequest(array_change_key_case($REQUEST, CASE_UPPER));
        Log::debug("Transaction Details: [%s]", var_export($RESPONSE_ARRAY, true));
        $this->_Response = $RESPONSE_ARRAY;
    }
    
    public function DoDirectPayment(PayerIdentity $PI, PayerCreditCard $PCC, PaymentDetails $PD)
    {
        $REQUEST = array(
            'METHOD' => 'DoDirectPayment'
        );
        $VALID_PI = $PI->is_valid();
        $VALID_PCC = $PCC->is_valid();
        $VALID_PD = $PD->is_valid();
        if($VALID_PI && $VALID_PCC && $VALID_PD)
        {
            $REQUEST = $this->CreateRequest($REQUEST, $PI->GetValidData(), $PCC->GetValidData(), $PD->GetValidData());
            $RESPONSE = $this->CURLRequest(array_change_key_case($REQUEST, CASE_UPPER));
            
            if(!is_array($RESPONSE) || !array_key_exists('ACK', $RESPONSE) || $RESPONSE === false)
            {
                $this->_Response = false;
                return false;
            }
            Log::debug("Direct Payment Response: [%s]", var_export($RESPONSE, true));
            $this->_Response = $RESPONSE;
            return true;
        }
        $this->_NotValid = array(
            'PayerIdentity' => $VALID_PI,
            'PayerCreditCard' => $VALID_PCC,
            'PaymentDetails' => $VALID_PD
        );
        return false;
    }
}

abstract class PayPalObject
{
    protected $Data = array();
    
    public static function Create($array)
    {
        $class = get_called_class();
        $OBJ = new $class();
        $OBJ->Data = array_merge($OBJ->Data, $array);
        if(method_exists($OBJ, '_JITSet'))
            $OBJ->_JITSet();
        return $OBJ;
    }
    
    public function is_valid()
    {
        $FILTERED = array_filter($this->Data, 'strlen');
        foreach($this->DataValidationRegex as $field => $regex)
            if(array_key_exists($field, $FILTERED) && preg_match($regex, $FILTERED[$field]) === false) return false;
        return true;
    }
    
    public function GetValidData()
    {
        return array_filter($this->Data, 'strlen');
    }
}

class PayerIdentity extends PayPalObject
{
    protected $Data = array(
        'ipaddress'         => null,
        'email'             => null,
        'payerid'           => null,
        'payerstatus'       => null,
        'business'          => null,
        'salutation'        => null,
        'firstname'         => null,
        'middlename'        => null,
        'lastname'          => null,
        'suffix'            => null,
        'street'            => null,
        'street2'           => null,
        'city'              => null,
        'state'             => null,
        'countrycode'       => null,
        'zip'               => null,
        'phonenum'          => null
    );
    
    protected $DataValidationRegex = array(
        'ipaddress'         => '/\b(?:(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(?:25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\b/',
        'email'             => '/^[\w\d\.]+@[\w\d]+\.[\w]{2,4}$/',
        'salutation'        => '/\w+/',
        'firstname'         => '/\w+/',
        'middlename'        => '/\w+/',
        'lastname'          => '/\w+/',
        'suffix'            => '/\w+/',
    );
    
    protected function _JITSet()
    {
        if(is_null($this->Data['ipaddress']))
            $this->Data['ipaddress'] = Controller::GetClientIP();
    }
}

class PayerCreditCard extends PayPalObject
{
    protected $Data = array(
        'creditcardtype'    => null,
        'acct'              => null,
        'expdate'           => null,
        'cvv2'              => null,
        'startdate'         => null,
        'issuenumber'       => null
    );
    
    public function is_valid()
    {
        $FILTERED = array_filter($this->Data, 'strlen');
        if(!array_key_exists('creditcardtype', $FILTERED)) return false;
        if(!array_key_exists('acct', $FILTERED)) return false;
        if(!array_key_exists('expdate', $FILTERED)) return false;
        if(!array_key_exists('cvv2', $FILTERED)) return false;
        return $this->checksum($FILTERED['acct']);
    }
    
    private function checksum($card_number) 
    {
        $card_number_checksum = '';
        foreach(str_split(strrev((string) $card_number)) as $i => $d)
            $card_number_checksum .= $i %2 !== 0 ? $d * 2 : $d;
        return array_sum(str_split($card_number_checksum)) % 10 === 0;
    }
}

class PaymentDetails extends PayPalObject
{
    protected $Data = array(
        'paymentaction'     => null,
        'amt'               => null,
        'currencycode'      => null,
        'itemamt'           => null,
        'shippingamt'       => null,
        'shipdiscamt'       => null,
        'handlingamt'       => null,
        'taxamt'            => null,
        'desc'              => null,
        'custom'            => null,
        'invnum'            => null,
        'notifyurl'         => null
    );
    
    protected $DataValidationRegex = array();
    
    protected function _JITSet()
    {
        if(is_null($this->Data['paymentaction']))
            $this->Data['paymentaction'] = 'Sale';
    }
}
?>