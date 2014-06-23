<?php
class Home extends Controller
{
    public function Index()
    {
        $this->Assign('title', 'Hello World, This is SKY! [v'.SKY::Version().']');
    }
}
