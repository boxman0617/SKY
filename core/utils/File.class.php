<?php
class SingleFile
{
    public $LocatorName;
    private $_file_data = array();
    
    public function __construct($locator_name, $file_array)
    {
        $this->LocatorName = $locator_name;
        $this->_file_data = $file_array;
    }
    
    public function __get($name)
    {
        if(array_key_exists($name, $this->_file_data))
            return $this->_file_data[$name];
        throw new Exception('No file data under ['.$name.']');
    }
    
    public function __set($name, $value)
    {
        if(array_key_exists($name, $this->_file_data))
        {
            $this->_file_data[$name] = $value;
            return true;
        }
        throw new Exception('No file data under ['.$name.']');
    }
    
    public function __isset($name)
    {
        return (isset($this->_file_data[$name]) && empty($this->_file_data[$name]));
    }
    
    public function HasProperty($name)
    {
        return array_key_exists($name, $this->_file_data);
    }
    
    public function IsEmpty()
    {
        return ($this->_file_data['error'] == UPLOAD_ERR_NO_FILE);
    }
    
    public function CheckIfValid()
    {
        if (
            !isset($this->_file_data['error']) ||
            is_array($this->_file_data['error'])
        ) {
            Log::corewrite('File uploaded is not valid. Data: [%s]', 4, __CLASS__, __FUNCTION__, array(var_export($this->_file_data, true)));
            throw new RuntimeException('Invalid parameters.');
        }
        
        switch($this->_file_data['error']) 
        {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                Log::corewrite('Exceeded filesize limit. Data: [%s]', 4, __CLASS__, __FUNCTION__, array(var_export($this->_file_data, true)));
                throw new RuntimeException('Exceeded filesize limit.');
            default:
                Log::corewrite('Unknown errors. Data: [%s]', 4, __CLASS__, __FUNCTION__, array(var_export($this->_file_data, true)));
                throw new RuntimeException('Unknown errors.');
        }
    }
}

class File
{
    private $_file_selected = null;
    private $_mime_types = array(
        'TEXT' => array(
            'txt' => 'text/plain',
            'htm' => 'text/html',
            'html' => 'text/html',
            'php' => 'text/html',
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'xml' => 'application/xml'
        ),
        'IMAGES' => array(
            'png' => 'image/png',
            'jpe' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'jpg' => 'image/jpeg',
            'gif' => 'image/gif',
            'bmp' => 'image/bmp',
            'ico' => 'image/vnd.microsoft.icon',
            'tiff' => 'image/tiff',
            'tif' => 'image/tiff',
            'svg' => 'image/svg+xml',
            'svgz' => 'image/svg+xml'
        ),
        'ARCHIVES' => array(
            'zip' => 'application/zip',
            'rar' => 'application/x-rar-compressed',
            'exe' => 'application/x-msdownload',
            'msi' => 'application/x-msdownload',
            'cab' => 'application/vnd.ms-cab-compressed'
        ),
        'MEDIA' => array(
            'mp3' => 'audio/mpeg',
            'qt' => 'video/quicktime',
            'mov' => 'video/quicktime',
            'swf' => 'application/x-shockwave-flash',
            'flv' => 'video/x-flv'
        ),
        'ADOBE' => array(
            'pdf' => 'application/pdf',
            'psd' => 'image/vnd.adobe.photoshop',
            'ai' => 'application/postscript',
            'eps' => 'application/postscript',
            'ps' => 'application/postscript'
        ),
        'MSOFFICE' => array(
            'doc' => 'application/msword',
            'rtf' => 'application/rtf',
            'xls' => 'application/vnd.ms-excel',
            'ppt' => 'application/vnd.ms-powerpoint'
        ),
        'OPENOFFICE' => array(
            'odt' => 'application/vnd.oasis.opendocument.text',
            'ods' => 'application/vnd.oasis.opendocument.spreadsheet'
        )
    );
    
    public static $FILES = array();
    public $FileDestination = null;
    public $FileType = null;
    public $FileMimeType = null;
    
    public function __construct($file_locator)
    {
        $this->FileDestination = SkyDefines::Call('DIR_PUBLIC');
        if(!array_key_exists($file_locator, self::$FILES))
            throw new FileNotFoundException();
        $this->_file_selected = $file_locator;
    }
    
    public function IsEmpty()
    {
        return self::$FILES[$this->_file_selected]->IsEmpty();
    }
    
    /**
     * ::AcceptOnly()
     * @params Array $types
     *  Example1: array('IMAGES')
     *  Example2: array('doc', 'rtf')
     *  Example3: array('MSOFFICE', 'odt')
     * 
     * @return Bool
     *  IF file is an accepted type: TRUE
     *  ELSE: FALSE
     */
    public function AcceptOnly($types = array())
    {
        if(!is_null($this->_file_selected))
        {
            self::$FILES[$this->_file_selected]->CheckIfValid();
            if(self::$FILES[$this->_file_selected]->tmp_name == '')
            {
                Log::corewrite('File upload failed. Data: [%s]', 4, __CLASS__, __FUNCTION__, array(var_export(self::$FILES[$this->_file_selected], true)));
                throw new Exception('File upload failed.');
            }
            $FILE_TYPE = mime_content_type(self::$FILES[$this->_file_selected]->tmp_name);
            $FILE = array(
                'category' => null,
                'type' => null
            );
            foreach($this->_mime_types as $TYPE => $TYPES)
            {
                if(in_array($FILE_TYPE, $TYPES))
                {
                    $FILE = array(
                        'category' => $TYPE,
                        'type' => array_search($FILE_TYPE, $TYPES)
                    );
                }
            }
            if(in_array($FILE['category'], $types) || in_array($FILE['type'], $types))
                return true;
            return false;
        }
        throw new FileNotSelectedException();
    }
    
    public function ChangeName($name, $salt = 'F1l3')
    {
        if(!is_null($this->_file_selected))
        {
            $EXT = pathinfo(self::$FILES[$this->_file_selected]->name, PATHINFO_EXTENSION);
            self::$FILES[$this->_file_selected]->name = md5($salt.$name).'.'.$EXT;
            return self::$FILES[$this->_file_selected]->name;
        }
        throw new FileNotSelectedException();
    }
    
    public function Move($to, $overwrite_file = false)
    {
        if(!is_null($this->_file_selected))
        {
            if($overwrite_file !== false)
            {
                if(file_exists($this->FileDestination.'/'.$to.'/'.$overwrite_file))
                    @unlink($this->FileDestination.'/'.$to.'/'.$overwrite_file);
            }
            if (!file_exists($this->FileDestination.'/'.$to))
                mkdir($this->FileDestination.'/'.$to, 0777, true);
            return move_uploaded_file(self::$FILES[$this->_file_selected]->tmp_name, $this->FileDestination.'/'.$to.'/'.self::$FILES[$this->_file_selected]->name);
        }
        throw new FileNotSelectedException();
    }
    
    public static function FilesCleanUp()
    {
        $CLEANED_FILES = array();
        foreach($_FILES as $name => $file_info_array)
        {
            if(is_array($file_info_array['name']))
            {
                foreach($file_info_array as $field => $values_array)
                {
                    foreach($values_array as $key => $value)
                    {
                        if(!isset($CLEANED_FILES[$name][$key]))
                            $CLEANED_FILES[$name][$key] = array();
                        $CLEANED_FILES[$name][$key][$field] = $value;
                    }
                }
            } else {
                $CLEANED_FILES[$name] = $file_info_array;
            }
        }
        return $CLEANED_FILES;
    }
    
    public static function RegisterFile($locator, SingleFile $data)
    {
        self::$FILES[$locator] = $data;
    }
}
?>