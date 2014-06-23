<?php
SKY::LoadCore();
SkyL::Import(SkyDefines::Call('DIR_CONFIGS').'/deploy.php');
SkyL::Import(SkyDefines::Call('FILETRANSFER_CLASS'));

Error::Supress(E_WARNING);
class Deployment
{
    private $CONFIG;
    private $TYPE;
    private $FT;
    private $Counter = 0;
    private $Total = 0;
    private $InstallingSKY = false;
    private $VERSION = null;
    private $PREVIOUS_VERSION = '0.0.0';
    private $CURRENT_VERSION = '0.0.0';
    
    public static $DB;
    
    public $cli = false;
    
    public function __construct($ENV, $TYPE = 'FTP')
    {
        $DC = new DeployConfig();
        $c = $DC->run();
        if(array_key_exists($ENV, $c))
        {
            $this->CONFIG = $c[$ENV];
            $this->TYPE = $TYPE;
        } else
            throw new Exception('Environment ['.$ENV.'] was not found in configuration.');
    }
    
    public function Deploy($options)
    {
        if(!array_key_exists(strtoupper($this->TYPE), $this->CONFIG))
            throw new Exception('Type ['.strtoupper($this->TYPE).'] is not defined in deploy.php');
        foreach($this->CONFIG[':options'] as $key => $value)
        {
            if(array_key_exists($key, $options))
            {
                if($options[$key] === 'true')
                    $this->CONFIG[':options'][$key] = true;
                elseif($options[$key] === 'false')
                    $this->CONFIG[':options'][$key] = false;
                else
                    $this->CONFIG[':options'][$key] = $options[$key];
            }
        }
        
        if(array_key_exists(':keep_track_of_version', $this->CONFIG[':options']))
        {
            $versions = array('INITIAL', 'BUGFIX', 'MINOR', 'MAJOR');
            foreach($options as $value)
            {
                if(in_array(strtoupper($value), $versions))
                    $this->VERSION = strtoupper($value);
            }
            if(is_null($this->VERSION))
                throw new Exception(':keep_track_of_version option requires a deployment type (INITIAL, BUGFIX, MINOR, MAJOR)');
        }
        
        if(!array_key_exists(':skycore_location', $this->CONFIG[':options']))
            throw new Exception(':skycore_location option is missing from configuration file!');
            
        if(!array_key_exists(':deployment_location', $this->CONFIG[':options']))
            throw new Exception(':deployment_location is missing from configuration file!');
        
        $this->TYPE = strtoupper($this->TYPE);
        $this->FT = FileTransfer::getProtocol($this->TYPE);
        $this->FT->SetConnectionCredentials($this->CONFIG[$this->TYPE]);
        if($this->cli)
            CommandLine::Puts($this->TYPE.' Connection established!');
        
        $INSTALL_SKYCORE_IF_MISSING = true;
        if(array_key_exists(':install_skycore_if_missing', $this->CONFIG[':options']))
            $INSTALL_SKYCORE_IF_MISSING = $this->CONFIG[':options'][':install_skycore_if_missing'];
        
        if($INSTALL_SKYCORE_IF_MISSING)
        {
            if($this->cli)
                CommandLine::Puts("\nChecking if SKY is installed...");
            $this->FT->SetWorkingDirectory('/');
            if(!$this->FT->DoesItExists('skycore'))
            {
                $this->InstallingSKY = true;
                if($this->cli)
                    CommandLine::Puts('SKY is NOT installed...');
                $this->Total = $this->GetFileCount(getenv('SKYCORE'));
                $this->MoveFiles(getenv('SKYCORE'));
                if($this->cli)
                    CommandLine::Puts("\nInstalled!");
                $this->InstallingSKY = false;
            } else {
                if($this->cli)
                    CommandLine::Puts('SKY is installed!');
            }
        }
        
        $BACKUP = false;
        if(array_key_exists(':backup', $this->CONFIG[':options']))
            $BACKUP = $this->CONFIG[':options'][':backup'];
            
        if($BACKUP)
        {
            if($this->cli)
                CommandLine::Puts("\nChecking if able to backup...");
            
            $this->FT->SetWorkingDirectory('/');
            if($this->FT->DoesItExists($this->CONFIG[':options'][':deployment_location']))
            {
                $this->FT->SetWorkingDirectory($this->CONFIG[':options'][':deployment_location']);
                if($this->FT->DoesItExists('deploy_version.info'))
                {
                    $fh = fopen('temp_deploy_version.info', 'w');
                    $this->FT->GetFileIntoOpenFile('deploy_version.info', $fh);
                    fclose($fh);
                    $this->PREVIOUS_VERSION = trim(file_get_contents('temp_deploy_version.info'));
                    unlink('temp_deploy_version.info');
                    
                    $this->FT->SetWorkingDirectory('..');
                    $this->FT->RenameIt($this->CONFIG[':options'][':deployment_location'], 'backup_'.date('mdYHis'));
                    if($this->cli)
                        CommandLine::Puts("Backup complete!");
                } else {
                    if($this->cli)
                        CommandLine::Puts("No deploy_version.info file found...? Moving on...");
                    $this->FT->SetWorkingDirectory('/');
                }
            } else {
                if($this->cli)
                    CommandLine::Puts("App not yet installed. Moving on...");
            }
        }
        
        // Deploying...
        if($this->cli)
            CommandLine::Puts("\nStarting deployment...");
        
        if(!is_null($this->VERSION))
        {
            if($this->VERSION != 'INITIAL')
            {
                $e = explode('.', $this->PREVIOUS_VERSION);
                $matrix = array(
                    'MAJOR' => intval($e[0]),
                    'MINOR' => intval($e[1]),
                    'BUGFIX' => intval($e[2])
                );
                if($this->VERSION == 'BUGFIX')
                    $matrix['BUGFIX']++;
                elseif($this->VERSION == 'MINOR')
                {
                    $matrix['MINOR']++;
                    $matrix['BUGFIX'] = 0;
                } else {
                    $matrix['MAJOR']++;
                    $matrix['MINOR'] = 0;
                    $matrix['BUGFIX'] = 0;
                }
                $this->CURRENT_VERSION = implode('.', $matrix);
            }
        }
        
        if(true)
        {
            $this->FT->SetWorkingDirectory('/');
            if($this->FT->DoesItExists($this->CONFIG[':options'][':deployment_location']))
            {
                $this->FT->RenameIt($this->CONFIG[':options'][':deployment_location'], 'delete_'.date('mdYHis'));
            }
            $this->Total = $this->GetFileCount(getcwd());
            $this->Counter = 0;
            $this->MoveFiles(getcwd());
        
        
            $cwd = basename(getcwd());
            if($cwd != $this->CONFIG[':options'][':deployment_location'])
            {
                $this->FT->SetWorkingDirectory('/');
                $this->FT->RenameIt($cwd, $this->CONFIG[':options'][':deployment_location']);
            }
            
            $this->FT->SetWorkingDirectory('/'.$this->CONFIG[':options'][':deployment_location']);
            
            $tempHandle = fopen('php://temp', 'r+');
            fwrite($tempHandle, trim($this->CURRENT_VERSION));
            rewind($tempHandle);
            $this->FT->CopyOpenedFile($tempHandle, 'deploy_version.info');
        }
        
        // $DB_DEPLOY = false;
        // if(array_key_exists(':database_deployment', $this->CONFIG[':options']))
        //     $DB_DEPLOY = $this->CONFIG[':options'][':database_deployment'];
        
        // if($DB_DEPLOY)
        // {
        //     if($this->cli)
        //         CommandLine::Puts("\nDeploying DB...");
            
        //     if(MODEL_DRIVER == 'MySQL')
        //     {
        //         exec('mysqldump -u '.DB_USERNAME.' -h '.DB_SERVER.' -p'.DB_PASSWORD.' '.DB_DATABASE.' > '.DB_DATABASE.'.sql');
        //         if($this->cli)
        //             CommandLine::Puts("MySQLDUMP Complete!");
                
        //         $this->FT->SetWorkingDirectory('/'.$this->CONFIG[':options'][':deployment_location']);
        //         $this->FT->CopyFile(getcwd().'/'.DB_DATABASE.'.sql');
        //     }
        // }
        
        return true;
    }
    
    private function MoveFiles($it)
    {
        if($this->cli)
            CommandLine::ShowProgressStatus($this->Counter, $this->Total, 50);
        if(is_dir($it))
        {
            $this->FT->MakeDir(basename($it));
            $this->FT->SetWorkingDirectory('./'.basename($it));
            if($dh = opendir($it))
            {
                while(($file = readdir($dh)) !== false)
                {
                    if($this->cli)
                        CommandLine::ShowProgressStatus($this->Counter, $this->Total, 50);
                    if($file == '.' || $file == '..')
                        continue;
                        
                    if(is_dir($it.'/'.$file))
                    {
                        $this->MoveFiles($it.'/'.$file);
                        $this->FT->SetWorkingDirectory('..');
                    } else {
                        if($file == '.htaccess')
                        {
                            $content = '';
                            if(array_key_exists(':htaccess_lang_handler', $this->CONFIG[':options']) && $this->CONFIG[':options'][':htaccess_lang_handler'] !== false)
                                $content .= 'AddHandler '.$this->CONFIG[':options'][':htaccess_lang_handler']."\n";
                            
                            $content .= "\n".file_get_contents($it.'/'.$file);
                            if(array_key_exists(':htaccess_rewrite_base', $this->CONFIG[':options']) && $this->CONFIG[':options'][':htaccess_rewrite_base'] !== false)
                                $content = str_replace('RewriteEngine On', "RewriteEngine On\nRewritebase ".$this->CONFIG[':options'][':htaccess_rewrite_base'], $content);
                                
                            if(!$this->InstallingSKY)
                            {
                                if(array_key_exists(':excluded_rewrite', $this->CONFIG[':options']) && $this->CONFIG[':options'][':excluded_rewrite'] != 'public|favicon.ico|lib/plugins')
                                {
                                    $content = preg_replace(
                                        '/RewriteRule \^\(\(\?!.+\)\.\*\) configs\/init\.php\?_query\=\$1 \[QSA,L\]/', 
                                        'RewriteRule ^((?!'.$this->CONFIG[':options'][':excluded_rewrite'].').*) configs/init.php?_query=$1 [QSA,L]', 
                                        $content
                                    );
                                }
                            }
                            
                            $tempHandle = fopen('php://temp', 'r+');
                            fwrite($tempHandle, trim($content));
                            rewind($tempHandle);
                            $this->FT->CopyOpenedFile($tempHandle, '.htaccess');
                        } elseif(!$this->InstallingSKY && $file == 'defines.php') {
                            if(array_key_exists(':app_enviroment', $this->CONFIG[':options']))
                            {
                                $content = preg_replace(
                                    '/\$GLOBALS\[\'ENV\'\]\ \=\ \'.+\'\;/',
                                    '\$GLOBALS[\'ENV\'] = \''.$this->CONFIG[':options'][':app_enviroment'].'\';', 
                                    file_get_contents($it.'/'.$file)
                                );
                                $tempHandle = fopen('php://temp', 'r+');
                                fwrite($tempHandle, $content);
                                rewind($tempHandle);
                                $this->FT->CopyOpenedFile($tempHandle, $file);
                            }
                        } elseif(basename($it) == 'bin') {
                            if(array_key_exists(':php_location', $this->CONFIG[':options']) && $this->CONFIG[':options'][':php_location'] !== false)
                            {
                                $content = preg_replace(
                                    '/\#!.+/', 
                                    '#!'.$this->CONFIG[':options'][':php_location'], 
                                    file_get_contents($it.'/'.$file)
                                );
                                $tempHandle = fopen('php://temp', 'r+');
                                fwrite($tempHandle, $content);
                                rewind($tempHandle);
                                $this->FT->CopyOpenedFile($tempHandle, $file);
                            } else {
                                $this->FT->CopyFile($it.'/'.$file);
                            }
                        } elseif($file == 'router_init.php') {
                            if(array_key_exists(':default_timezone', $this->CONFIG[':options']) && $this->CONFIG[':options'][':default_timezone'] != 'America/Los_Angeles')
                            {
                                $content = preg_replace("/date_default_timezone_set\('.+'\)\;/", "date_default_timezone_set('".$this->CONFIG[':options'][':default_timezone']."');", file_get_contents($it.'/'.$file));
                                
                                $tempHandle = fopen('php://temp', 'r+');
                                fwrite($tempHandle, $content);
                                rewind($tempHandle);
                                $this->FT->CopyOpenedFile($tempHandle, $file);
                            } else {
                                $this->FT->CopyFile($it.'/'.$file);
                            }
                        } else {
                            $this->FT->CopyFile($it.'/'.$file);
                        }
                        $this->Counter++;
                    }
                }
                closedir($dh);
            }
        }
    }
    
    public function GetFileCount($path) 
    {
        $size = 0;
        $ignore = array('.', '..', 'cgi-bin', '.DS_Store');
        $files = scandir($path);
        foreach($files as $t) {
            if(in_array($t, $ignore)) continue;
            if (is_dir(rtrim($path, '/') . '/' . $t))
                $size += $this->GetFileCount(rtrim($path, '/') . '/' . $t);
            else
                $size++;
        }
        return $size;
    }
}
