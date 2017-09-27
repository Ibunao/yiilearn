<?php
namespace app\controllers\ding;

use Yii;
use yii\web\Controller;

class TestController extends Controller
{
    /**
     * ?r=ding/test/test
     * @return [type] [description]
     */
	public function actionTest()
	{
		var_dump($this);
	}

}
