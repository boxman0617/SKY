<?php
interface SkyCommand
{
	public function __construct($cli);
	public function GetShortHelp();
	public function GetLongHelp();
	public function Execute($args = array());
}
