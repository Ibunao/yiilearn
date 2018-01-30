<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use app\events\Cat;
use app\events\Mourse;
use app\events\MyMourse;
use yii\base\Event;

class AnimalController extends Controller
{
	public function actionIndex()
	{
		$cat = new Cat;
		$mourse = new Mourse;
		// 绑定事件,并传递数据here  
		$cat->on('miao', [$mourse, 'run'], 'here');
		// 调用miao的方法，触发事件  
		$cat->shout();
		/*
		输出：
		miao 
		hereI am running! 
		 */
	}
	public function actionMyIndex()
	{
		$cat = new Cat;
		$mourse = new MyMourse;
		// 绑定事件,并传递携带数据的$event对象  
		$cat->on('miao', [$mourse, 'run']);
		// 调用miao的方法，触发事件  
		$cat->shout1();
		/*
		输入：
		miao 
		myeventI am running! 
		 */
	}
	public function actionIndexEvent()
	{
		$cat = new Cat;
		$mourse = new Mourse;
		// 类绑定事件  
		Event::on(Cat::className(),'miao', [$mourse, 'run'], 'here');
		// 调用miao的方法，触发事件，对象级触发  
		// $cat->shout();
		/*
		输出：
		miao 
		hereI am running! 
		 */
		// 类级别触发
		Event::trigger(Cat::className(),'miao');
		/*
		输出：
		hereI am running! 
		 */
	}
}