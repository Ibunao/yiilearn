<?php

namespace app\modules\ding\controllers;

use Yii;
use yii\web\Controller;
use yii\helpers\VarDumper;
use app\models\Clocking;
use yii\db\StaleObjectException;
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
    /**
     * 乐观锁测试
     * @return [type] [description]
     */
    public function actionLock($id)
    {
        $this->layout = '@app/views/layouts/main';
        $model = Clocking::findOne($id);
        try {
            if (Yii::$app->request->getIsPost() && $model->load(Yii::$app->request->post()) && $model->save()) {
                echo "更新成功";
                return;
            } else {
                return $this->render('lock', [
                    'model' => $model,
                ]);
            }
        } catch (StaleObjectException $e) {
            echo "更新失败";
            // 解决冲突的代码
        }
    }
}
