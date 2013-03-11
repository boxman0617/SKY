<?php
class ErrorPage extends Controller
{
    public function Notfound()
    {
        $this->Assign('error', '404 Not Found');
    }
}
?>