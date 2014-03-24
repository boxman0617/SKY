<?php
SkyL::Import(SkyDefines::Call('DATACACHE_CLASS'));

class TestObj
{
	public $a = 'A';
	public $b = 'B';
	public $c = 1234;
}

class DataCacheTest
{
	private $key = 'test_blah';
	public function CacheAndCheckIfCachedValueIsSameAsInput()
	{
		$data = array('blah' => 123);
		DataCache::Cache($this->key, $data, '3 seconds');
		$cache = DataCache::GetCache($this->key);

		TestMaster::AssertSame($data, $cache, 'Cache output is not the same as input!');
	}

	public function CheckExpired()
	{
		sleep(3);
		TestMaster::Assert(!DataCache::HasCache($this->key, '3 seconds'), 'Cache still exists! Should have been expired.');
	}

	public function CacheAnObject()
	{
		$t = new TestObj();
		DataCache::Cache($this->key, $t, '3 seconds');
		$data = DataCache::GetCache($this->key);

		TestMaster::AssertEqual($t, $data, 'Cache output is not equal as input!');
	}

	public function CheckIfNotExpired()
	{
		$data = array('blah' => 123);
		DataCache::Cache($this->key, $data, '10 seconds');
		$cache = DataCache::GetCache($this->key);

		TestMaster::Assert(DataCache::HasCache($this->key, '10 seconds'), 'Cache expired! Should not be.');
	}

	public function ClearingCache()
	{
		DataCache::Cache($this->key, 'Going to be cleared');
		TestMaster::Assert(DataCache::HasCache($this->key), 'Something went wrong! Did not cache.');
		
		DataCache::Clear($this->key);
		TestMaster::Assert(!DataCache::HasCache($this->key), 'Something went wrong! Cache still exists.');
	}

	private function CreateKey()
	{
		$alph = 'abcdefghijklmnopqrstuvwxyz';
		$size = rand(5, 10);
		$key = '';
		for($i = 0; $i < $size; $i++)
			$key .= $alph[rand(0, 25)];
		return $key;
	}

	public function LoadTest()
	{
		$start = microtime(true);
		$keys = array();
		for($i = 0; $i < 10000; $i++)
		{
			$data = array_fill(0, rand(100, 1000), array('blah' => rand()));
			$key = $this->CreateKey();
			DataCache::Cache($key, $data);
			$keys[] = $key;
		}

		foreach($keys as $key)
			DataCache::Clear($key);
		$end = microtime(true);

		TestMaster::Assert((90 > ($end - $start)), '10000 Load test took too long!');
	}
}
?>