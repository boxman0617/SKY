<?php
class FTP extends FileTransfer
{
    private $_C = false;
    private $_Attempts = 0;
    private $_Allowed = 10;
    
    public function SetConnectionCredentials($creds)
    {
        $this->Credentials = $creds;
        $this->FTPConnect();
    }
    
    private function FTPConnect()
    {
        $PARAMS = array($this->Credentials['host']);
        if(array_key_exists('port', $this->Credentials))
            $PARAMS[] = $this->Credentials['port'];
        $this->_C = call_user_func_array('ftp_connect', $PARAMS);
        if($this->_C !== false)
        {
            if(!ftp_login($this->_C, $this->Credentials['username'], $this->Credentials['password']))
                throw new Exception('Incorrect username/password!');
        } else
            throw new Exception('Unable to connect to FTP host ['.$this->Credentials['host'].']');
    }
    
    public function SetWorkingDirectory($dir)
    {
        ftp_pasv($this->_C, true);
        $this->WorkingDirectory = $dir;
        if(!ftp_chdir($this->_C, $this->WorkingDirectory))
        {
            Log::debug('Unable to set directory ['.$this->WorkingDirectory.']. Trying again...');
            sleep(3);
            if(!ftp_chdir($this->_C, $this->WorkingDirectory))
                throw new Exception('Could not set directory!');
        }
    }
    
    public function DoesItExists($it)
    {
        ftp_pasv($this->_C, true);
        $files = ftp_nlist($this->_C, $this->WorkingDirectory);
        if(empty($files))
        {
            $f = fopen('php://temp', 'r+');
            $mode = FTP_ASCII;
            $ascii = array('php', 'info', 'txt', 'sql');
            if(!(!in_array(pathinfo($it, PATHINFO_EXTENSION), $ascii) || $it != '.htaccess'))
                $mode = FTP_BINARY;
            $result = ftp_fget($this->_C, $f, $it, $mode);
            fclose($f);
            return $result;
        }
        return in_array($it, $files);
    }
    
    public function MakeDIr($dir)
    {
        ftp_pasv($this->_C, true);
        ftp_mkdir($this->_C, $dir);
    }
    
    public function CopyFile($file)
    {
        ftp_pasv($this->_C, true);
        $mode = FTP_ASCII;
        $ascii = array('php', 'info', 'txt', 'sql');
        if(!(!in_array(pathinfo($file, PATHINFO_EXTENSION), $ascii) || $file != '.htaccess'))
            $mode = FTP_BINARY;
        if(!ftp_put($this->_C, basename($file), $file, $mode))
        {
            $this->_Attempts++;
            while($this->_Attempts < $this->_Allowed)
            {
                Log::debug("Something went wrong with [".basename($file)."]. Trying again... [".$this->_Attempts."]");
                sleep(3);
                ftp_pasv($this->_C, true);
                if(!ftp_put($this->_C, basename($file), $file, $mode))
                {
                    $this->_Attempts++;
                } else {
                    $this->_Attempts = 0;
                    return true;
                }
                
            }
            throw new Exception('FTP: Unable to copy ['.$file.']');
        }
    }
    
    public function ShowCurrentDir()
    {
        ftp_pasv($this->_C, true);
        return ftp_pwd($this->_C);
    }
    
    public function CopyOpenedFile($file_handler, $file_name)
    {
        ftp_pasv($this->_C, true);
        $mode = FTP_ASCII;
        $ascii = array('php', 'info', 'txt', 'sql');
        if(!(!in_array(pathinfo($file_name, PATHINFO_EXTENSION), $ascii) || $file_name != '.htaccess'))
            $mode = FTP_BINARY;
        ftp_fput($this->_C, $file_name, $file_handler, $mode);
    }
    
    public function GetFileIntoOpenFile($file, $file_handler)
    {
        ftp_pasv($this->_C, true);
        $mode = FTP_ASCII;
        $ascii = array('php', 'info', 'txt', 'sql');
        if(!(!in_array(pathinfo($file, PATHINFO_EXTENSION), $ascii) || $file != '.htaccess'))
            $mode = FTP_BINARY;
        ftp_fget($this->_C, $file_handler, $file, $mode, 0);
    }
    
    public function RenameIt($old, $new)
    {
        ftp_pasv($this->_C, true);
        ftp_rename($this->_C, $old, $new);
    }
}
?>