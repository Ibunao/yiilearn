<?php

// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
//composer 自动加载
require(__DIR__ . '/../vendor/autoload.php');
// yii自带自动加载
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
// 配置信息
$config = require(__DIR__ . '/../config/web.php');

(new yii\web\Application($config))->run();