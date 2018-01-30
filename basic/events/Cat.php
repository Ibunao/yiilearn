<?php
namespace app\events;
use yii\base\Component;
use app\events\MyEvent;
/**
* 猫，需要继承Component
*/
class Cat extends Component
{
	public function shout()
	{
		echo "miao <br/>";
		// 触发miao事件  
		$this->trigger('miao');
	}
	public function shout1()
	{
		$event = new MyEvent;
		echo "miao <br/>";
		// 触发miao事件  
		$this->trigger('miao', $event);
	}
}