<?php
class ErrorPage extends Controller
{
    public function Notfound()
    {
    	$this->SetLayout('layout/notfound.view.php');
        $this->Assign('error', '404 Not Found');
    }
}
