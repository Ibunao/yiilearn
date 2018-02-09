<?php

namespace app\controllers;

use Yii;
use app\helpers\zController;
use yii\db\Query;
use yii\di\Instance;
use yii\db\Connection;
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

		// var_dump(Instance::of(null));
		// 容器
		// Yii::$container->get('app\events\Mourse');
		// return $this->renderAjax('test');

		var_dump(__CLASS__);exit;

        // returns Yii::$app->db
        $db = Instance::ensure('db', Connection::className());
        // returns an instance of Connection using the given configuration
        $db = Instance::ensure(['dsn' => 'sqlite:path/to/my.db'], Connection::className());
	}
	public function actionDb()
	{
		$sql = 'select [[ding]] from {{%ran%}}  WHERE id=:id AND status=:status;';
		$db = Yii::$app->db;
		var_dump($db->createCommand($sql)->bindValue(':id', "'1'--")
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
	public function actionCache()
	{
		// Yii::$app->cache->set('ding', false);
		/*$ding = Yii::$app->cache->get('ding', );
		var_dump($ding);
		$ding = Yii::$app->cache->exists('ding');
		var_dump($ding);*/
		$cache = Yii::$app->cache;
		// $dependency = new \yii\caching\FileDependency(['fileName'=>'hw.txt', 'reusable' => true]);
		// $cache->set('file_key','hello world!', 3000, $dependency);
		// sleep(60);
		var_dump($cache->get('file_key'));
	}
	public function actionCommand()
	{
		$db = Yii::$app->db;
		// // [[ ]] 是要加反引号的，和数据库系统的关键词区分开
		// $sql = 'select [[name]] from {{%admin_users}}  WHERE status=:status;';
		// var_dump($query = $db->createCommand($sql)
		// 	// 第三个参数设置数据格式
  //          ->bindValue(':status', 1, \PDO::PARAM_STR)->query());
		// // 设置pdo读取数据的格式
		// $query->setFetchMode(\PDO::FETCH_BOTH);

		// foreach ($query as $key => $value) {
		// 	var_dump($key, $value);
		// }

		// 插入不可以使用bindValue
		// var_dump($query = $db->createCommand()->insert('{{%admin_users}}', ['status'=>':status', 'super'=> ':super'])->bindValue(':status', 1)->bindValue(':super', 1)->execute());

		(new Query)->where(['in', 'a' , [10, 30]])
            ->andWhere(['between', 'b', 10, 20])
            ->orWhere('status=:status', [':status' => 1])
            ->all();
	}
	public function actionQuery()
	{
		$query = (new Query())
		    ->from('{{%admin_users}}')
		    ->orderBy('user_id');

		foreach ($query->batch() as $users) {
		    // $users 是一个包含100条或小于100条用户表数据的数组
		}

	}
	public function actionRequest()
	{
		$request = Yii::$app->getRequest();
		// $result = $request->getUrl();
		// $result = $request->getScriptUrl();
		// $result = $request->resolve();
		// $result = $request->getHeaders();
		// $result = $request->getPathInfo();
		// $result = $request->getQueryString();
		$result = $request->getServerName();
		$result2 = $request->getUserIP();
		$result3 = $request->getUserHost();
		$result4 = $request->getAuthUser();
		$result5 = $request->getAuthPassword();
		$result6 = $request->getAuthPassword();

		var_dump($result,$result2,$result3,$result4,$result5,$result6);
	}
	public function actionCookie()
	{
		var_dump($_COOKIE);
	}
	public function actionResponse()
	{
		Yii::$app->response->sendContentAsFile('ding', 'ding.html');
	}
	public function actionSession()
	{
		// session_start();
		// var_dump($_SESSION);exit;
		$session = Yii::$app->session;
		// Yii::$app->session->cookieParams = ['lifetime' => 30];
		// var_dump($session->setId('12223234'));
		Yii::$app->session->open();
		// $session->set('ding', 'ranafsdfadfadsfsddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddddd');
		// $session->regenerateID();
		// echo $session->getSavePath();
		// // $session->destroy();
		// echo $session->get('ding');
		$session->setFlash('ding', 'ran');
		echo $session->getFlash('ding');
		Yii::$app->session->open();
		// var_dump($_SESSION);
		echo $session->getFlash('ding');
		// echo $session->get('__ding');

	}
}
