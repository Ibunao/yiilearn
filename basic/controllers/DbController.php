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
		$db->getDriverName();
		$sql = $db->createCommand('SELECT * FROM user LIMIT 10');
		$sql->queryAll();
	}
}
