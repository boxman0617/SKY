<?php
if(!file_exists(DIR_APP_MODELS.'/Skyqueues.model.php'))
{
	import(DBBUILD_CLASS);
    $a = array(
        'SKYQueue.php',
        'skyqueues',
        'name:varchar_255',
        'info:text'
    );
    $build = new DBBuild($a);
    $build->HandleInput();
}

class SKYQueue
{
	public static function Append($queue_name, $item)
	{
		$sq = new Skyqueues();
		$sq->name = $queue_name;
		$sq->info = serialize($item);
		$sq->save();
	}

	public static function Next($queue_name)
	{
		$sq = new Skyqueues();
		$sqr = $sq->where('name = ?', $queue_name)->limit(1)->run();
		$item = $sqr->info;
		if(!is_null($item))
		{
			$sqr->delete();
			return unserialize($item);
		}
		return false;
	}

	public static function Count($queue_name)
	{
		$sq = new Skyqueues();
		$sqr = $sq->select('count(*) as `count`')->where('name = ?', $queue_name)->run();
		return $sqr->count;
	}

	public static function Clear($queue_name)
	{
		$sq = new Skyqueues();
		$sqr = $sq->where('name = ?', $queue_name)->run();
		$sqr->delete_set();
	}
}
?>