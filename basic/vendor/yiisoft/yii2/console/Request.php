<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\console;

/**
 * The console Request represents the environment information for a console application.
 *
 * It is a wrapper for the PHP `$_SERVER` variable which holds information about the
 * currently running PHP script and the command line arguments given to it.
 *
 * @property array $params The command line arguments. It does not include the entry script name.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Request extends \yii\base\Request
{
    // 参数
    private $_params;


    /**
     * 获取命令行参数
     * Returns the command line arguments.
     * @return array the command line arguments. It does not include the entry script name.
     */
    public function getParams()
    {
        if ($this->_params === null) {
            // cli模式（命令行）下，第一个参数$_SERVER['argv'][0]是脚本名，其余的是传递给脚本的参数
            if (isset($_SERVER['argv'])) {
                $this->_params = $_SERVER['argv'];
                // 将数组开头的单元移出数组
                array_shift($this->_params);
            } else {
                $this->_params = [];
            }
        }

        return $this->_params;
    }

    /**
     * 设置参数
     * Sets the command line arguments.
     * @param array $params the command line arguments
     */
    public function setParams($params)
    {
        $this->_params = $params;
    }

    /**
     * 获取路由和参数
     * Resolves the current request into a route and the associated parameters.
     * @return array the first element is the route, and the second is the associated parameters.
     * @throws Exception when parameter is wrong and can not be resolved
     */
    public function resolve()
    {
        // 获取全部的命令行参数
        $rawParams = $this->getParams();
        // 除了路由的参数,也就是参数是否以 -- 开始
        $endOfOptionsFound = false;
        // 获取路由
        // 第一个参数作为路由
        if (isset($rawParams[0])) {
            //将数组开头的单元移出数组  路由
            $route = array_shift($rawParams);
            // 如果第一个参数是 -- 
            if ($route === '--') {
                $endOfOptionsFound = true;
                $route = array_shift($rawParams);
            }
        } else {
            $route = '';
        }

        $params = [];
        // 获取参数
        foreach ($rawParams as $param) {
            if ($endOfOptionsFound) {
                $params[] = $param;
            // -- 单独的 -- 坑能是一种中断符吧，表示到参数部分了  ，没用过
            } elseif ($param === '--') {
                $endOfOptionsFound = true;
            // 正则怎么解??? 问号 ? 在冒号: 前或者后都可以
            // 用法见http://www.yiichina.com/doc/guide/2.0/tutorial-console
            // --key=value 表示为属性赋值  
            } elseif (preg_match('/^--([\w-]+)(?:=(.*))?$/', $param, $matches)) {
                $name = $matches[1];
                // 如果第一字符是数字
                if (is_numeric(substr($name, 0, 1))) {
                    throw new Exception('Parameter "' . $name . '" is not valid');
                }
                // appconfig 配置参数
                if ($name !== Application::OPTION_APPCONFIG) {
                    $params[$name] = isset($matches[2]) ? $matches[2] : true;
                }
            // 参数key使用了别名 ，一个字母
            } elseif (preg_match('/^-([\w-]+)(?:=(.*))?$/', $param, $matches)) {
                $name = $matches[1];
                if (is_numeric($name)) {
                    $params[] = $param;
                } else {
                    $params['_aliases'][$name] = isset($matches[2]) ? $matches[2] : true;
                }
            } else {
                $params[] = $param;
            }
        }

        return [$route, $params];
    }
}
