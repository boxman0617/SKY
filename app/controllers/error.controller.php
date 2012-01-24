<?php
class Error extends Controller
{
    public function Notfound()
    {
        $this->Assign('error', '404 Not Found');
    }
}
?>