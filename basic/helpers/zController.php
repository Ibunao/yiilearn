<?php
namespace app\helpers;
use \yii\web\Controller;//使用web
use yii\base\InlineAction;

class zController extends Controller
{
    /**
     * Author:Steven 原作者
     * Desc:重写路由，处理访问控制器支持驼峰命名法
     * @param string $id
     * @return null|object|InlineAction
     */
    public function createAction($id)
    {
        if ($id === '') {
            $id = $this->defaultAction;
        }

        $actionMap = $this->actions();
        if (isset($actionMap[$id])) {
            return \Yii::createObject($actionMap[$id], [$id, $this]);
        } elseif (preg_match('/^[a-z0-9\\-_]+$/', $id) && strpos($id, '--') === false && trim($id, '-') === $id) {
            $methodName = 'action' . str_replace(' ', '', ucwords(implode(' ', explode('-', $id))));
            if (method_exists($this, $methodName)) {
                $method = new \ReflectionMethod($this, $methodName);
                if ($method->isPublic() && $method->getName() === $methodName) {
                    return new InlineAction($id, $this, $methodName);
                }
            }
        // 驼峰形式,支持第一个字母小写
        } else {
        	$id = ucfirst($id);
            $methodName = 'action' . $id;
            if (method_exists($this, $methodName)) {
                $method = new \ReflectionMethod($this, $methodName);
                if ($method->isPublic() && $method->getName() === $methodName) {
                    return new InlineAction($id, $this, $methodName);
                }
            }
        }
        return null;
    }
}