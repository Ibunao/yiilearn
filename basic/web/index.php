<?php

// comment out the following two lines when deployed to production
defined('YII_DEBUG') or define('YII_DEBUG', true);
defined('YII_ENV') or define('YII_ENV', 'dev');
//composer 自动加载
require(__DIR__ . '/../vendor/autoload.php');
// yii 自动加载 简单初始化
require(__DIR__ . '/../vendor/yiisoft/yii2/Yii.php');
// 加载配置信息
$config = require(__DIR__ . '/../config/web.php');
// 
(new yii\web\Application($config))->run();
