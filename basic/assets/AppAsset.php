<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace app\assets;

use yii\web\AssetBundle;

/**
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class AppAsset extends AssetBundle
{
    # 指定资源文件放在 @webroot 目录下 web/
    public $basePath = '@webroot';
    # 对应的URL为 @web
    public $baseUrl = '@web';
    # 资源包中包含一个CSS文件 web/css/site.css
    public $css = [
        'css/site.css',
    ];
    # 资源包中包含js文件
    public $js = [
    ];
    # 依赖
    public $depends = [
        'yii\web\YiiAsset',
        'yii\bootstrap\BootstrapAsset',
    ];
}
