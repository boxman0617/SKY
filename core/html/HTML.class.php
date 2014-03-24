<?php
/**
 * HTML Core Class
 *
 * This class allows user to build HTML elements
 * programmability.
 *
 * LICENSE:
 *
 * This file may not be redistributed in whole or significant part, or
 * used on a web site without licensing of the enclosed code, and
 * software features.
 *
 * @author      Alan Tirado <root@deeplogik.com>
 * @copyright   2013 DeepLogik, All Rights Reserved
 * @license     http://www.codethesky.com/license
 * @link        http://www.codethesky.com/docs/htmlclass
 * @package     Sky.Core
 */

class HTML
{
    private $single = array(
        'input',
        'br',
        'img'
    );

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