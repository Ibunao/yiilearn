<?php
/**
 * Yii bootstrap file.
 *
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */
// 在加载 BaseYii.php 的时候会预先定义一些变量
require(__DIR__ . '/BaseYii.php');

/**
 * Yii is a helper class serving common framework functionalities.
 *
 * It extends from [[\yii\BaseYii]] which provides the actual implementation.
 * By writing your own Yii class, you can customize some functionalities of [[\yii\BaseYii]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Yii extends \yii\BaseYii
{
}
//注册自动加载
spl_autoload_register(['Yii', 'autoload'], true, true);
//设置类与路径的加载映射
// 设置类路径映射可以加快自动加载的速度
Yii::$classMap = require(__DIR__ . '/classes.php');
// 容器
Yii::$container = new yii\di\Container();
