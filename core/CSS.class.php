<?php
class CSS
{
	public $file;
	public $values = array();

	public function __construct($file)
	{
		$file = DIR_ROOT."/../".$file;
		if(!file_exists($file))
		{
			header('HTTP/1.0 404 Not Found');
			exit();
		}
		$this->file = $file;
	}

	public function Parse()
	{
		$content = '';
		$lines = file($this->file);
		foreach($lines as $line)
		{
			$content .= $this->Replace($line);
		}
		return $content;
	}

	public function Replace($line)
	{
		preg_match_all('/\s*\\$([A-Za-z1-9_\-]+)(\s*:\s*(.*?);)?\s*/', $line, $vars);
	    $found     = $vars[0];
	    $varNames  = $vars[1];
	    $varValues = $vars[3];
	    $count     = count($found);

	    for($i = 0; $i < $count; $i++)
	    {
	        $varName  = trim($varNames[$i]);
	        $varValue = trim($varValues[$i]);
	        if($varValue)
	        {
	            $this->values[$varName] = $this->Replace($varValue);
	        }
	        else if (isset($this->values[$varName]))
	        {
	            $line = preg_replace('/\\$'.$varName.'(\W|\z)/', $this->values[$varName].'\\1', $line);
	        }
	    }
	    $line = str_replace($found, '', $line);
	    return $line;
	}

	public function Display()
	{
		header('Content-type: text/css');
		echo $this->Parse();
	}
}
?>