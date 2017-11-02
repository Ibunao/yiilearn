<?php

namespace app\controllers;

use Yii;
use app\helpers\zController;

class TestController extends zController
{
	public function behaviors()
	{
		return ['app\behaviors\TestBehavior'];
	}
	public function actionTest()
	{
		// $ding = [1,2,3];
		// //获取类名
		// echo $this->className();//app\controllers\TestController
		// //别名alisas
		// // Yii::setAlias('@ding/ran/bunao', 'basic/ding/ran/bunao ');

		// // var_dump($this->aliases());
		// echo Yii::t('app', 'ding');

		// var_dump(Yii::$app->request->getScriptUrl());exit;
		// var_dump(Yii::$app->request->getBaseUrl());exit;
		// var_dump(Yii::$app->request->getUrl());exit;
		// var_dump(Yii::$app->request->getHostInfo());exit;
		// var_dump(Yii::$app->request->getHostName());exit;
		// var_dump(Yii::$app->request->getScriptFile());exit;
		// var_dump(Yii::$app->request->getServerName());exit;
		// var_dump(Yii::$app->request->getCookies());exit;
		// var_dump($_COOKIE);exit;

		// return $this->run('site/index');

		// 容器
		// Yii::$container->get('app\controllers\SiteController');

		return $this->renderAjax('test');
	}
	public function actionDb()
	{
		$sql = 'select [[ding]] from {{%ran%}}  WHERE id=:id AND status=:status;';
		var_dump(Yii::$app->db->createCommand($sql)->bindValue(':id', 1)
           ->bindValue(':status', 1)->execute());
	}
	public function actionBunao()
	{
		var_dump($_GET);
		echo "here";
	}
	public function actionDingBunao()
	{
		echo "bunao";
	}
}
