<?php
class UninitializedChildPropertyException extends Exception
{
    protected $message = 'Uninitialized Child Property';    // exception message
    protected $code = 1001;                                 // user defined exception code
}

class ModelAssociationException extends Exception
{
    protected $message = 'An exception was encountered in the Modeling Association logic';    // exception message
    protected $code = 2001;                                 // user defined exception code
}

class ModelIOException extends Exception
{
    protected $message = 'An exception was encountered in the Modeling Input/Output logic';    // exception message
    protected $code = 2002;                                 // user defined exception code
}

class ModelReadOnlyException extends Exception
{
    protected $message = 'Unable to save record. Model set to ReadOnly mode.';    // exception message
    protected $code = 2003;                                 // user defined exception code
}

class FileNotFoundException extends Exception
{
    protected $message = 'File not found in registered files.';    // exception message
    protected $code = 3000;                                 // user defined exception code
}

class FileNotSelectedException extends Exception
{
    protected $message = 'No file was selected.';    // exception message
    protected $code = 3001;                                 // user defined exception code
}

class InvalidRouteException extends Exception
{
    protected $message = 'Route does not exists.';
    protected $code = 4000;
}

class NoScriptFoundException extends Exception
{
    protected $message = 'Unable to fork! No script found for [';
    protected $code = 5000;

    public function __construct($script)
    {
        parent::__construct($this->message.$script.']', $this->code);
    }
}

class ForkException extends Exception
{
    protected $message = 'Unable to fork';
    protected $code = 5001;
}

class ProcessDisconnectException extends Exception
{
    protected $message = 'Unable to push/pull data due to unknown reason.';
    protected $code = 5002;
}

class ImportException extends Exception
{
  protected $message = 'Unable to Import file.';
  protected $code = 6000;
}
