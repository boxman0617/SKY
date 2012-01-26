<?php
class Plugin_Test
{
    public function IncludeCSS($obj)
    {
        $smarty_assign = $obj->smarty_assign;
        if(isset($smarty_assign['css']))
        {
            $smarty_assign['css'][] = '/lib/plugins/test/test.css';
            $obj->smarty_assign = $smarty_assign;
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