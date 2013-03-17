<?php
interface RenderInterface
{
	public function Render(&$render_info);
}

class RenderHTML implements RenderInterface
{
	public function Render(&$render_info)
	{
		if($render_info['status'] == NOT_RENDERED)
		{
			extract(Controller::$_variables);
			include_once(DIR_APP_VIEWS.'/'.$render_info['layout']);
			$render_info['status'] = RENDERED;
		}
	}
}

class RenderJSON implements RenderInterface
{
	public function Render(&$render_info)
	{
		if(isset($render_info['info']))
		{
			echo json_encode($render_info['info']);
			return true;
		}
		echo json_encode(array());
		return false;
	}
}

class RenderXML implements RenderInterface
{
	public function Render(&$render_info)
	{
		@header("Content-type:application/xml");
		echo $this->xml_encode((object)$render_info['info'], 'array');
	}

	private function xml_encode($value, $tag = "root") 
	{ 
	  	if( !is_array($value) 
			&& !is_string($value)
			&& !is_bool($value)
			&& !is_numeric($value)
			&& !is_object($value) ) {
				return false;
		}
		function x2str($xml, $key) 
		{
			if (!is_array($xml) && !is_object($xml)) {
				return "<$key>".htmlspecialchars($xml)."</$key>";      
			}
			$xml_str="";
			foreach ($xml as $k=>$v) 
			{   
				if(is_numeric($k)) {
					$k = "_".$k;
				}
				$xml_str.=x2str($v,$k);       
			}    
			return "<$key>$xml_str</$key>";  
		}
		return simplexml_load_string(x2str($value,$tag))->asXml();
	}

	private function xml_decode($xml) 
	{
		if(!is_string($xml)) return false;
		return @simplexml_load_string($xml);
	}
}

class RenderSUBVIEWS implements RenderInterface
{
	public function Render(&$render_info)
	{

	}
}
?>