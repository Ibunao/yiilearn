<?php

namespace app\modules\ding;

/**
 * ding module definition class
 */
class Ding extends \yii\base\Module
{
    /**
     * @inheritdoc
     */
    public $controllerNamespace = 'app\modules\ding\controllers';

    public $layout = 'ding.php';
    /**
     * @inheritdoc
     */
    public function init()
    {
        parent::init();
        // custom initialization code goes here
    }
}
