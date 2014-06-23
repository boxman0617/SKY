<?php
interface RenderInterface
{
    public function __construct(&$ref);
	public function Render(&$render_info);
}

class RenderHTML extends Base implements RenderInterface
{
	public static $_subview_render_cache = null;
    protected $REF;
    public function __construct(&$ref)
    {
        $this->REF = $ref;
    }
    
	public function Render(&$render_info)
	{
		if($render_info['status'] == Controller::NOT_RENDERED)
		{
			extract(Controller::$_variables);
			if(is_null(self::$_subview_render_cache))
			{
				$prop = call_user_func(get_class($this->REF).'::GetStaticProperty', '_subview_info');
				$view = SkyDefines::Call('DIR_APP_VIEWS').'/'.$prop['dir'].'/'.$prop['view'].'.view.php';
        		if(file_exists($view))
        		{
        			ob_start();
        			call_user_func(get_class($this->REF).'::RenderSubView');
					self::$_subview_render_cache = ob_get_clean();
        		}
			}
            ob_start();
            Log::corewrite('OB Level [%s]', 3, __CLASS__, __FUNCTION__, array(ob_get_level()));
    		include_once(SkyDefines::Call('DIR_APP_VIEWS').'/'.$render_info['layout']);
    		Log::corewrite('Number of errors [%s]', 3, __CLASS__, __FUNCTION__, array(Error::ErrorCount()));
    		if(Error::IsThereErrors())
    		{
    		    Log::corewrite('Errors found while rendering!', 3, __CLASS__, __FUNCTION__);
    		    ob_end_clean();
    		    Error::Flush();
    		    return false;
    		}
            ob_flush();
			$render_info['status'] = Controller::RENDERED;
		}
	}
}

class RenderJSON implements RenderInterface
{
    protected $REF;
    public function __construct(&$ref)
    {
        $this->REF = $ref;
    }
    
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
    protected $REF;
    public function __construct(&$ref)
    {
        $this->REF = $ref;
    }
    
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
    protected $REF;
    public function __construct(&$ref)
    {
        $this->REF = $ref;
    }
    
	public function Render(&$render_info)
	{

	}
}
