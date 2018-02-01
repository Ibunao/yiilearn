<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use Yii;
use Closure;
use yii\base\Component;
use yii\base\InvalidConfigException;
/*
服务定位器

 */
/**
 * ServiceLocator implements a [service locator](http://en.wikipedia.org/wiki/Service_locator_pattern).
 *
 * To use ServiceLocator, you first need to register component IDs with the corresponding component
 * definitions with the locator by calling [[set()]] or [[setComponents()]].
 * You can then call [[get()]] to retrieve a component with the specified ID. The locator will automatically
 * instantiate and configure the component according to the definition.
 *
 * For example,
 *
 * ```php
 * $locator = new \yii\di\ServiceLocator;
 * $locator->setComponents([
 *     'db' => [
 *         'class' => 'yii\db\Connection',
 *         'dsn' => 'sqlite:path/to/file.db',
 *     ],
 *     'cache' => [
 *         'class' => 'yii\caching\DbCache',
 *         'db' => 'db',
 *     ],
 * ]);
 *
 * $db = $locator->get('db');  // or $locator->db
 * $cache = $locator->get('cache');  // or $locator->cache
 * ```
 *
 * Because [[\yii\base\Module]] extends from ServiceLocator, modules and the application are all service locators.
 *
 * For more details and usage information on ServiceLocator, see the [guide article on service locators](guide:concept-service-locator).
 *
 * @property array $components The list of the component definitions or the loaded component instances (ID =>
 * definition or instance).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ServiceLocator extends Component
{
    /**
     * 用于缓存服务、组件等的实例
     * @var array shared component instances indexed by their IDs
     */
    private $_components = [];
    /**
     * 用于保存服务和组件的定义，通常为配置数组，可以用来创建具体的实例
     * @var array component definitions indexed by their IDs
     */
    private $_definitions = [];


    /**
     * Getter magic method.
     * This method is overridden to support accessing components like reading properties.
     * @param string $name component or property name
     * @return mixed the named property value
     */
    public function __get($name)
    {
        // has() 方法就是判断 $_definitions 数组中是否已经保存了服务或组件的定义
        // 请留意，这个时候服务或组件仅是完成定义，不一定已经实例化
        if ($this->has($name)) {
            // get() 方法用于返回服务或组件的实例
            return $this->get($name);
        } else {
            // 未定义的服务或组件，那么视为正常的属性、行为，
            // 调用 yii\base\Component::__get()
            return parent::__get($name);
        }
    }

    /**
     * Checks if a property value is null.
     * This method overrides the parent implementation by checking if the named component is loaded.
     * @param string $name the property name or the event name
     * @return bool whether the property value is null
     */
    public function __isset($name)
    {
        if ($this->has($name)) {
            return true;
        } else {
            return parent::__isset($name);
        }
    }

    /**
     * Returns a value indicating whether the locator has the specified component definition or has instantiated the component.
     * This method may return different results depending on the value of `$checkInstance`.
     *
     * - If `$checkInstance` is false (default), the method will return a value indicating whether the locator has the specified
     *   component definition.
     * - If `$checkInstance` is true, the method will return a value indicating whether the locator has
     *   instantiated the specified component.
     *
     * @param string $id component ID (e.g. `db`).
     * @param bool $checkInstance whether the method should check if the component is shared and instantiated.
     * @return bool whether the locator has the specified component definition or has instantiated the component.
     * @see set()
     */
    /**
     * 检查是否有某个服务或组件
     * @param  [type]  $id            [description]
     * @param  boolean $checkInstance [description]
     *  当 $checkInstance === false 时，用于判断是否已经定义了某个服务或组件
     * 当 $checkInstance === true 时，用于判断是否已经有了某个服务或组件的实例
     * @return boolean                [description]
     */
    public function has($id, $checkInstance = false)
    {
        return $checkInstance ? isset($this->_components[$id]) : isset($this->_definitions[$id]);
    }

    /**
     * Returns the component instance with the specified ID.
     *
     * @param string $id component ID (e.g. `db`).
     * @param bool $throwException whether to throw an exception if `$id` is not registered with the locator before.
     * @return object|null the component of the specified ID. If `$throwException` is false and `$id`
     * is not registered before, null will be returned.
     * @throws InvalidConfigException if `$id` refers to a nonexistent component ID
     * @see has()
     * @see set()
     */
    public function get($id, $throwException = true)
    {
        // 是否存在   已经实例化
        if (isset($this->_components[$id])) {
            return $this->_components[$id];
        }
        // 存在定义
        if (isset($this->_definitions[$id])) {
            $definition = $this->_definitions[$id];
            // 如果定义是个对象，且不是Closure对象，那么直接将这个对象返回
            // 通过定义创建对象，并保存到 _components
            if (is_object($definition) && !$definition instanceof Closure) {
                return $this->_components[$id] = $definition;
            } else {
                // 是个数组或者PHP callable，调用 Yii::createObject()来创建一个实例
                // Yii::createObject 服务定位器和容器进行关联
                return $this->_components[$id] = Yii::createObject($definition);
            }
        } elseif ($throwException) {
            throw new InvalidConfigException("Unknown component ID: $id");
        } else {
            return null;
        }
    }

    /**
     * Registers a component definition with this locator.
     *
     * For example,
     *
     * ```php
     * // a class name
     * $locator->set('cache', 'yii\caching\FileCache');
     *
     * // a configuration array
     * $locator->set('db', [
     *     'class' => 'yii\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // an anonymous function
     * $locator->set('cache', function ($params) {
     *     return new \yii\caching\FileCache;
     * });
     *
     * // an instance
     * $locator->set('cache', new \yii\caching\FileCache);
     * ```
     *
     * If a component definition with the same ID already exists, it will be overwritten.
     *
     * @param string $id component ID (e.g. `db`).
     * @param mixed $definition the component definition to be registered with this locator.
     * It can be one of the following:
     *
     * - a class name
     * - a configuration array: the array contains name-value pairs that will be used to
     *   initialize the property values of the newly created object when [[get()]] is called.
     *   The `class` element is required and stands for the the class of the object to be created.
     * - a PHP callable: either an anonymous function or an array representing a class method (e.g. `['Foo', 'bar']`).
     *   The callable will be called by [[get()]] to return an object associated with the specified component ID.
     * - an object: When [[get()]] is called, this object will be returned.
     *
     * @throws InvalidConfigException if the definition is an invalid configuration array
     */
    /**
     * 
     * @param [type] $id         组件id
     * @param [type] $definition 组件定义
     */
    public function set($id, $definition)
    {
        // 从组件数组删除此id
        unset($this->_components[$id]);
        // 删除
        if ($definition === null) {
            unset($this->_definitions[$id]);
            return;
        }
        // 保存定义
        if (is_object($definition) || is_callable($definition, true)) {
            // an object, a class name, or a PHP callable
            $this->_definitions[$id] = $definition;
        } elseif (is_array($definition)) {
            // a configuration array
            if (isset($definition['class'])) {
                $this->_definitions[$id] = $definition;
            } else {
                throw new InvalidConfigException("The configuration for the \"$id\" component must contain a \"class\" element.");
            }
        } else {
            throw new InvalidConfigException("Unexpected configuration type for the \"$id\" component: " . gettype($definition));
        }
    }

    /**
     * 
     * Removes the component from the locator.
     * @param string $id the component ID
     */
    public function clear($id)
    {
        unset($this->_definitions[$id], $this->_components[$id]);
    }

    /**
     * Returns the list of the component definitions or the loaded component instances.
     * @param bool $returnDefinitions whether to return component definitions instead of the loaded component instances.
     * @return array the list of the component definitions or the loaded component instances (ID => definition or instance).
     */
    public function getComponents($returnDefinitions = true)
    {
        return $returnDefinitions ? $this->_definitions : $this->_components;
    }

    /**
     * Registers a set of component definitions in this locator.
     *
     * This is the bulk version of [[set()]]. The parameter should be an array
     * whose keys are component IDs and values the corresponding component definitions.
     *
     * For more details on how to specify component IDs and definitions, please refer to [[set()]].
     *
     * If a component definition with the same ID already exists, it will be overwritten.
     *
     * The following is an example for registering two component definitions:
     *
     * ```php
     * [
     *     'db' => [
     *         'class' => 'yii\db\Connection',
     *         'dsn' => 'sqlite:path/to/file.db',
     *     ],
     *     'cache' => [
     *         'class' => 'yii\caching\DbCache',
     *         'db' => 'db',
     *     ],
     * ]
     * ```
     *
     * @param array $components component definitions or instances
     */
    public function setComponents($components)
    {
        foreach ($components as $id => $component) {
            $this->set($id, $component);
        }
    }
}
