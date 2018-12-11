<?php

namespace app\modules\ding\controllers;

use Yii;
use yii\web\Controller;
use yii\helpers\VarDumper;

/**
 * Default controller for the `ding` module
 */
class DefaultController extends Controller
{
    /**
     * Renders the index view for the module
     * @return string
     */
    public function actionIndex()
    {
        // echo $this->module;exit;
        // echo $this->module->getBasePath();exit;
        // $this->layout = 'main';
        // return 'ding';
        return $this->render('index');
    }
    /**
     * 测试日志
     * @return [type] [description]
     */
    public function actionLog()
    {
        Yii::warning('test', 'ding');
    }
    public function actionDb()
    {
        $command = Yii::$app->db->createCommand('select [[level_id]], [[level_name]] from {{%level}} where level_name = :name', [':name' => '主力款']);
        // 获取要执行的sql
        echo $command->getRawSql();
        // 查询
        $temp = $command->queryOne();
        VarDumper::dump($temp);
    }
}
