<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\debug;

use Yii;
use yii\base\Component;
use yii\helpers\ArrayHelper;
use yii\helpers\Url;

/**
 * Panel是调试器面板类的基类。它定义了如何收集数据，在调试工具栏和调试器细节视图中应该显示什么
 * Panel is a base class for debugger panel classes. It defines how data should be collected,
 * what should be displayed at debug toolbar and on debugger details view.
 *
 * @property string $detail Content that is displayed in debugger detail view. This property is read-only.
 * @property string $name Name of the panel. This property is read-only.
 * @property string $summary Content that is displayed at debug toolbar. This property is read-only.
 * @property string $url URL pointing to panel detail view. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Panel extends Component
{
    /**
     * @var string panel unique identifier.
     * It is set automatically by the container module.
     */
    public $id;
    /**
     * @var string request data set identifier.
     */
    public $tag;
    /**
     * @var Module
     */
    public $module;
    /**
     * @var mixed data associated with panel
     */
    public $data;
    /**
     * @var array array of actions to add to the debug modules default controller.
     * This array will be merged with all other panels actions property.
     * See [[\yii\base\Controller::actions()]] for the format.
     */
    public $actions = [];


    /**
     * @return string name of the panel
     */
    public function getName()
    {
        return '';
    }

    /**
     * @return string content that is displayed at debug toolbar
     */
    public function getSummary()
    {
        return '';
    }

    /**
     * @return string content that is displayed in debugger detail view
     */
    public function getDetail()
    {
        return '';
    }

    /**
     * Saves data to be later used in debugger detail view.
     * This method is called on every page where debugger is enabled.
     *
     * @return mixed data to be saved
     */
    public function save()
    {
        return null;
    }

    /**
     * Loads data into the panel
     *
     * @param mixed $data
     */
    public function load($data)
    {
        $this->data = $data;
    }

    /**
     * @param null|array $additionalParams Optional additional parameters to add to the route
     * @return string URL pointing to panel detail view
     */
    public function getUrl($additionalParams = null)
    {
        $route = [
            '/' . $this->module->id . '/default/view',
            'panel' => $this->id,
            'tag' => $this->tag,
        ];

        if (is_array($additionalParams)){
            $route = ArrayHelper::merge($route, $additionalParams);
        }

        return Url::toRoute($route);
    }

    /**
     * Returns a trace line
     * @param array $options The array with trace
     * @return string the trace line
     * @since 2.0.7
     */
    public function getTraceLine($options)
    {
        if (!isset($options['text'])) {
            $options['text'] = "{$options['file']}:{$options['line']}";
        }
        $traceLine = $this->module->traceLine;
        if ($traceLine === false) {
            return $options['text'];
        } else {
            $options['file'] = str_replace('\\', '/', $options['file']);
            $rawLink = $traceLine instanceof \Closure ? call_user_func($traceLine, $options, $this) : $traceLine;
            return strtr($rawLink, ['{file}' => $options['file'], '{line}' => $options['line'], '{text}' => $options['text']]);
        }
    }
}
