<?php
/**
 * API publisher class
 * 
 * It allows services to be extended out and allowed to publish
 * methods as an API.
 * 
 * LICENSE:
 * The MIT License (MIT)
 * 
 * Copyright (c) 2014 DeeplogiK
 * 
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 * 
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 * 
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 * 
 * @author      Alan Tirado <alan@deeplogik.com>
 * @copyright   2014 DeepLogik, All Rights Reserved
 * @license     MIT
 * @package     Core\api\PublishAPI
 * @version     1.0.0
 */

Event::SubscribeActionHook('/Route/query/ready/before/', 'PublishAPI::Test');

interface APIService
{
	public function HandleNoMethodCall();
	public function HandleSecutiry();
}

/**
 * Base class for publishable API classes
 */
class PublishAPI
{
	private static $Instance = null;

	/**
	 * Singleton method
	 * 
	 * This will return the only instance of this class
	 */
	public static function GetInstance()
	{
		if(is_null(self::$Instance))
			self::$Instance = new PublishAPI();
		return self::$Instance;
	}

	public static function SecureWithAPIKey($keys, $get_key = 'api_key')
	{
		if(!array_key_exists($get_key, $_GET))
			throw new Exception('Insecure API Call!');

		if(is_string($keys))
		{
			if($keys == $_GET[$get_key])
				return true;
		}
		elseif(is_array($keys))
		{
			if(in_array($_GET[$get_key], $keys))
				return true;
		}

		throw new Exception('Incorrect API KEY!');
	}

	/**
	 * Test if this request is an API request
	 * 
	 * If the request to the Router starts with the API_BASE_ROUTE,
	 * this method will intercept that request and handle it
	 * 
	 * @param string $query The outter query sent to the Router
	 * @param string $method The request method sent to the Router
	 * 
	 * @return void
	 */
	public static function Test($query, $method)
	{
		$url_parts = explode('/', $query);
		if($url_parts[0] == SkyDefines::Call('API_BASE_ROUTE'))
		{
			$p = self::GetInstance();
			$p->Follow($method, $query);
		}
	}

	public function Follow($method, $query)
	{
		$stack = $this->ParseQuery($query);
		if(empty($stack))
			return $this->HandleEmptyRequest();

		$action = array_shift($stack);
		$class = $this->LoadAPIClasses($action);

		$this->HandleRequest($class, $stack);
		die('API');
	}

	private function HandleRequest($class, $params)
	{
		$obj = new $class();
		if(($obj instanceof APIService) === false)
			throw new Exception('Requested class is not a valid implementation of APIService.');
		if(empty($params))
			return $obj->HandleNoMethodCall();

		$expected_method = array_shift($params);
		$method = $this->ParseObjectMethod($expected_method);

		$obj->HandleSecutiry();

		if(method_exists($obj, $method))
		{
			$result = $obj->$method($params);
			if(array_key_exists(':type', $result))
			{
				$output = 'Handle'.$result[':type'];
				if(method_exists($this, $output))
					$this->$output($result[':data']);
			}
		}
	}

	private function HandleJSON($array)
	{
		header('Content-Type: application/json');
		echo json_encode($array);
		exit();
	}

	private function HandleEmptyRequest()
	{
		die('Empty Request');
		return true;
	}

	private function ParseObjectMethod($expected_method)
	{
		return SKY::UnderscoreToUpper($expected_method);
	}

	private function ParseQuery($query)
	{
		$explode = explode('/', $query);
		array_shift($explode); // Removing API_BASE_ROUTE
		return $explode;
	}

	private function LoadAPIClasses($action)
	{
		$class = strtolower($action);
		$dir = SkyDefines::Call('DIR_APP_API');
		if(is_dir($dir)) 
        {
            if($dh = opendir($dir)) 
            {
                while(($file = readdir($dh)) !== false)
                {
                	if($file == '.' || $file == '..') continue;
                	if($class.'.api.php' == $file)
                	{
                		SkyL::Import($dir.'/'.$file);
                		return 'API'.ucfirst($class);
                	}
                }
                closedir($dh);
            }
        }

        return false;
	}
}
?>