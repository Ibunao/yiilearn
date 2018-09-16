<?php

namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\db\Query;
use yii\di\Instance;
use yii\db\Connection;
use app\models\Orders;
class DbController extends Controller
{
	public function actionIndex()
	{
		$db = Yii::$app->db;
		$command = $db->createCommand('SELECT * FROM {{%agent}} LIMIT 10');
		// 获取要执行的sql
		$sql = $command->getRawSql();
		// $command->execute();
		$command->queryAll();
	}
	public function actionCache()
	{
		$db = Yii::$app->db;
		$db->createCommand('SELECT * FROM {{%agent}} LIMIT 10')->queryAll();
		// $customer = $db->cache(function (Connection $db) {
  //        	return $db->createCommand('SELECT * FROM {{%agent}} LIMIT 10')->queryAll();
  //    	});
	}
	public function actionAr()
	{
		$orders = Orders::find()
		// ->select(['meet_purchase.purchase_name'])
	 //    ->where(['order_id' => 2017031349481025])
	 //    ->leftJoin('meet_purchase', 'meet_purchase.purchase_id = meet_order.purchase_id')
		// ->asArray()
	 //    ->one();
		->batch(10);
	}
}
