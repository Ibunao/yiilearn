<?php
namespace app\helpers;

use Yii;
use yii\web\UrlRule;

/**
* 自定义rule解析规则
*/
class DingUrlRule extends UrlRule
{
	public function parseRequest($manager, $request)
	{
		parseRequest($manager, $request);
	}
}