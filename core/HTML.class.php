<?php
class HTML
{
    private $single = array(
        'input',
        'br',
        'img'
    );

    public function __construct()
    {

    }

    public function __call($tag, $params)
    {
        if(is_array($params[0]))
        {
            $html = "<".$tag;
            foreach($params[0] as $attr => $value)
            {
                if($attr != "innerHTML")
                {
                    $html .= " ".$attr."='".$value."'";
                }
            }
            if(in_array($tag, $this->single))
            {
                $html .= " />";
            } else {
                $html .= ">";
                if(isset($params[0]['innerHTML']))
                {
                    $html .= $params[0]['innerHTML'];
                }
                $html .= "</".$tag.">";
            }
        } else {
            $html = "<".$tag;
            if(isset($params[1]) && is_array($params[1]))
            {
                foreach($params[1] as $attr => $value)
                {
                    if($attr != "innerHTML")
                    {
                        $html .= " ".$attr."='".$value."'";
                    }
                }
                if(in_array($tag, $this->single))
                {
                    $html .= " />";
                } else {
                    $html .= ">";
                    $html .= $params[0];
                    $html .= "</".$tag.">";
                }
            } else {
                $html .= ">".$params[0]."</".$tag.">";
            }
        }
        return $html;
    }
}
?>