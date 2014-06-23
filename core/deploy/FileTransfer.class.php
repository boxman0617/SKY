<?php
abstract class FileTransfer
{
    protected $WorkingDirectory = '/';
    protected $Credentials = array();
    
    public function SetConnectionCredentials($creds)
    {
        $this->Credentials = $creds;
    }
    
    public static function getProtocol($type)
    {
        if(file_exists(SkyDefines::Call('SKYCORE_CORE_DEPLOY').'/'.$type.'.class.php'))
        {
            SkyL::Import(SkyDefines::Call('SKYCORE_CORE_DEPLOY').'/'.$type.'.class.php');
            return new $type();
        }
    }
    
    abstract public function SetWorkingDirectory($dir);
    abstract public function DoesItExists($it);
    abstract public function MakeDIr($dir);
}
