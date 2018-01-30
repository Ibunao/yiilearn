<?php
namespace app\events;
use yii\base\Event;
/**
* 自定义事件类
*/
class MyEvent extends Event
{
	public $myName = 'myevent';
}