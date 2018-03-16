<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\db\Query;
use yii\di\Instance;
use yii\db\Connection;
class DbController extends Controller
{
	public function actionIndex()
	{
		$db = Yii::$app->db;
		$command = $db->createCommand('SELECT * FROM {{%agent}} LIMIT 10');
		var_dump($command->queryAll());
	}
	public function actionCache()
	{
		$db = Yii::$app->db;
		$db->createCommand('SELECT * FROM {{%agent}} LIMIT 10')->queryAll();
		// $customer = $db->cache(function (Connection $db) {
  //        	return $db->createCommand('SELECT * FROM {{%agent}} LIMIT 10')->queryAll();
  //    	});
	}
}
