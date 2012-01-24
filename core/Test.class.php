<?php

class Test
{
    private $pass = 0;
    private $total = 0;
    private $fail = 0;
    
    public function __construct($params)
    {
        
    }
    
    public function HandleInput($input)
    {
        if(strpos($input, ':'))
        {
            $options = explode(':', $input);
            $this->RunSingleTest($options[0], $options[1]);
        } else {
            $this->RunTests($input);
        }
        
    }
    
    private function RunSingleTest($type, $test)
    {
        
    }
    
    private function RunTests($type)
    {
        
    }
    
    public function AssertTrue($a, $b)
    {
        if($a == $b)
        {
            ++$this->pass;
        } else {
            ++$this->fail;
        }
    }
    
    public function AssertFalse($a, $b)
    {
        if($a != $b)
        {
            ++$this->pass;
        } else {
            ++$this->fail;
        }
    }
}

class Blah
{
    public function test_one()
    {
        $this->AssertTrue(true, true);
    }
    
    public function test_two()
    {
        $this->AssertFalse(true, false);
    }
    
    private function test_helper()
    {
        
    }
}

$c = new Blah();
?>