<?php
interface SkyAuthUser
{
    public function GetPassword();
    public function SetPassword($password);
    public function GetRole();
    public function GetGroup();
}
