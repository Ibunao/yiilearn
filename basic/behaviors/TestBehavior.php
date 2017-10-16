<?php
namespace app\behaviors;

use yii\base\Behavior;
/**
* 行为测试
*/
class TestBehavior extends Behavior
{
	
	public function actionDing()
	{
		echo "string";
	}
}