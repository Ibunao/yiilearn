<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\rbac;

/**
 * 权限对象
 * For more details and usage information on Permission, see the [guide article on security authorization](guide:security-authorization).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Permission extends Item
{
    /**
     * @inheritdoc
     */
    public $type = self::TYPE_PERMISSION;
}
