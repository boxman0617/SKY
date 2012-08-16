<?php
class Plugin_Test
{
    public function IncludeCSS($obj)
    {
        $variables = $obj->variables;
        if(isset($variables['css']))
        {
            $variables['css'][] = '/lib/plugins/test/test.css';
            $obj->variables = $variables;
        }   
        else
            $obj->Assign('css', array('/lib/plugins/test/test.css'));
    }
    public function AddSomething()
    {
        echo '<div class="test_footer">This is a footer. Time is: '.date('H:i:s A').'</div>';
    }
}
?>