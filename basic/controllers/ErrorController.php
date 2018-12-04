<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

/**
 * 测试ErrorHandler
 */
class ErrorController extends Controller
{
	public function actionIndex()
	{
		// 触发错误，错误再异常
		// echo $foo['bar'];  // 由于数组未定义，会产生一个notice级别的错误  
		// trigger_error('人为触发一个错误', E_USER_ERROR); //人为触发错误  
		// foobar(3, 5);   //调用未定义的方法将会产生一个Error级别的错误  
		var_dump('here');
		// throw new Exception("Error Processing Request", 1);
		
		echo Yii::$app->state;
	}
}
