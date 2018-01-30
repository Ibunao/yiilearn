<?php
namespace app\events;

/**
* 鼠
*/
class MyMourse
{
	public function run($event)
	{
		echo $event->myName;
		echo "I am running! <br/>";
	}
}