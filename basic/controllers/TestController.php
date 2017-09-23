<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;

class TestController extends Controller
{
	public function actionTest()
	{
		//获取类名
		echo $this->className();//app\controllers\TestController

		//别名alisas
		Yii::setAlias('@ding/ran/bunao', 'basic/ding/ran/bunao');
		var_dump($this->aliases());
		echo Yii::t('app', 'ding');
	}

}
