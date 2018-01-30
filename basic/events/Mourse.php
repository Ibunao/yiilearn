<?php
namespace app\events;

/**
* 鼠
*/
class Mourse
{
	public function __construct($ding, $ran)
	{
		
	}
	public function run($event)
	{
		echo $event->data;
		echo "I am running! <br/>";
	}
}
