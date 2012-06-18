<?php
class HTML
{
    private $html = "";

    public function __construct()
    {
        
    }

    public function __call($tag, $settings)
    {
        if(substr($tag, 0, 5) == 'close')
        {
            $tmp = '';
            for($i=5;$i<strlen($tag);$i++)
                $tmp .= $tag[$i];
            $html = '</'.$tmp.'>';
        } else {
            $html = '<'.$tag;
            foreach($settings as $attr => $value)
            {
                $html .= ' '.$attr.'="'.$value.'"';
            }
            $html .= '>';
        }
        $this->html .= $html;
    }

    public function innerHTML($text)
    {
        $this->html .= $text;
    }

    public function Output()
    {
        echo $this->html;
    }
}
?>
