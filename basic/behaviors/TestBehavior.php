<?php
namespace app\behaviors;

use yii\base\Behavior;
use yii\web\Controller;
use yii\base\Event;
/**
* 行为测试
*/
class TestBehavior extends Behavior
{
	// public function events()
	// {
	// 	return [
	// 		Controller::EVENT_AFTER_ACTION => 'abc',
	// 	];
	// }
	public function init()
	{
		parent::init();
		Event::on(Controller::classname(), Controller::EVENT_AFTER_ACTION, [$this, 'abc']);
	}
	public function abc($event)
	{
		echo "abc";exit;
	}
	public function actionDing()
	{
		echo "string";
	}
}