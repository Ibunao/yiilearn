<?php

namespace app\modules\ding\controllers;

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
}
