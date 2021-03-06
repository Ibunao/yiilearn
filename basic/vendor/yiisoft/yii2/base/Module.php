<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;
use yii\di\ServiceLocator;

/**
 * Module is the base class for module and application classes.
 *
 * A module represents a sub-application which contains MVC elements by itself, such as
 * models, views, controllers, etc.
 *
 * A module may consist of [[modules|sub-modules]].
 *
 * [[components|Components]] may be registered with the module so that they are globally
 * accessible within the module.
 *
 * For more details and usage information on Module, see the [guide article on modules](guide:structure-modules).
 *
 * @property array $aliases List of path aliases to be defined. The array keys are alias names (must start
 * with `@`) and the array values are the corresponding paths or aliases. See [[setAliases()]] for an example.
 * This property is write-only.
 * @property string $basePath The root directory of the module.
 * @property string $controllerPath The directory that contains the controller classes. This property is
 * read-only.
 * @property string $layoutPath The root directory of layout files. Defaults to "[[viewPath]]/layouts".
 * @property array $modules The modules (indexed by their IDs).
 * @property string $uniqueId The unique ID of the module. This property is read-only.
 * @property string $version The version of this module. Note that the type of this property differs in getter
 * and setter. See [[getVersion()]] and [[setVersion()]] for details.
 * @property string $viewPath The root directory of view files. Defaults to "[[basePath]]/views".
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Module extends ServiceLocator
{
    /**
     * 定义在执行 action之前触发的事件方法
     * @event ActionEvent an event raised before executing a controller action.
     * You may set [[ActionEvent::isValid]] to be `false` to cancel the action execution.
     */
    const EVENT_BEFORE_ACTION = 'beforeAction';
    /**
     * 定义在执行 action之后触发的事件方法
     * @event ActionEvent an event raised after executing a controller action.
     */
    const EVENT_AFTER_ACTION = 'afterAction';

    /**
     * 定义的参数
     * @var array custom module parameters (name => value).
     */
    public $params = [];
    /**
     *
     * @var string an ID that uniquely identifies this module among other modules which have the same [[module|parent]].
     */
    public $id;
    /**
     * 父module
     * @var Module the parent module of this module. `null` if this module does not have a parent.
     */
    public $module;
    /**
     * @var string|bool the layout that should be applied for views within this module. This refers to a view name
     * relative to [[layoutPath]]. If this is not set, it means the layout value of the [[module|parent module]]
     * will be taken. If this is `false`, layout will be disabled within this module.
     */
    public $layout;
    /**
     * 用id映射到指定的控制器，这可以实现跨module， 使用别的module的控制器
     * @var array mapping from controller ID to controller configurations.
     * Each name-value pair specifies the configuration of a single controller.
     * A controller configuration can be either a string or an array.
     * If the former, the string should be the fully qualified class name of the controller.
     * If the latter, the array must contain a `class` element which specifies
     * the controller's fully qualified class name, and the rest of the name-value pairs
     * in the array are used to initialize the corresponding controller properties. For example,
     *
     * ```php
     * [
     *   'account' => 'app\controllers\UserController',
     *   'article' => [
     *      'class' => 'app\controllers\PostController',
     *      'pageTitle' => 'something new',
     *   ],
     * ]
     * ```
     */
    public $controllerMap = [];
    /**
     * module下控制器的目录
     * @var string the namespace that controller classes are in.
     * This namespace will be used to load controller classes by prepending it to the controller
     * class name.
     *
     * If not set, it will use the `controllers` sub-namespace under the namespace of this module.
     * For example, if the namespace of this module is `foo\bar`, then the default
     * controller namespace would be `foo\bar\controllers`.
     *
     * See also the [guide section on autoloading](guide:concept-autoloading) to learn more about
     * defining namespaces and how classes are loaded.
     */
    public $controllerNamespace;
    /**
     * 默认的路由
     * @var string the default route of this module. Defaults to `default`.
     * The route may consist of child module ID, controller ID, and/or action ID.
     * For example, `help`, `post/create`, `admin/post/create`.
     * If action ID is not given, it will take the default value as specified in
     * [[Controller::defaultAction]].
     */
    public $defaultRoute = 'default';

    /**
     * @var string the root directory of the module.
     */
    private $_basePath;
    /**
     * @var string the root directory that contains view files for this module
     */
    private $_viewPath;
    /**
     * @var string the root directory that contains layout view files for this module.
     */
    private $_layoutPath;
    /**
     * 存放配置文件里的modules信息
     * @var array child modules of this module
     */
    private $_modules = [];
    /**
     * @var string|callable the version of this module.
     * Version can be specified as a PHP callback, which can accept module instance as an argument and should
     * return the actual version. For example:
     *
     * ```php
     * function (Module $module) {
     *     //return string|int
     * }
     * ```
     *
     * If not set, [[defaultVersion()]] will be used to determine actual value.
     *
     * @since 2.0.11
     */
    private $_version;


    /**
     * Constructor.
     * @param string $id the ID of this module.
     * @param Module $parent the parent module (if any).
     * @param array $config name-value pairs that will be used to initialize the object properties.
     */
    public function __construct($id, $parent = null, $config = [])
    {
        $this->id = $id;
        $this->module = $parent;
        parent::__construct($config);
    }

    /**
     * 返回请求的当前模块
     * Returns the currently requested instance of this module class.
     * If the module class is not currently requested, `null` will be returned.
     * This method is provided so that you access the module instance from anywhere within the module.
     * @return static|null the currently requested instance of this module class, or `null` if the module class is not requested.
     */
    public static function getInstance()
    {
        $class = get_called_class();
        return isset(Yii::$app->loadedModules[$class]) ? Yii::$app->loadedModules[$class] : null;
    }

    /**
     * 设置当前请求模块的实例
     * Sets the currently requested instance of this module class.
     * @param Module|null $instance the currently requested instance of this module class.
     * If it is `null`, the instance of the calling class will be removed, if any.
     */
    public static function setInstance($instance)
    {
        if ($instance === null) {
            // 后期静态绑定（"Late Static Binding"）类的名称
            unset(Yii::$app->loadedModules[get_called_class()]);
        } else {
            Yii::$app->loadedModules[get_class($instance)] = $instance;
        }
    }

    /**
     * 创建控制器的路径
     * Initializes the module.
     *
     * This method is called after the module is created and initialized with property values
     * given in configuration. The default implementation will initialize [[controllerNamespace]]
     * if it is not set.
     *
     * If you override this method, please make sure you call the parent implementation.
     */
    public function init()
    {
        // 如果没定以控制器目录，则根据module类名生成默认的目录
        if ($this->controllerNamespace === null) {
            $class = get_class($this);
            if (($pos = strrpos($class, '\\')) !== false) {
                $this->controllerNamespace = substr($class, 0, $pos) . '\\controllers';
            }
        }
    }

    /**
     * 返回模块的id
     * Returns an ID that uniquely identifies this module among all modules within the current application.
     * Note that if the module is an application, an empty string will be returned.
     * @return string the unique ID of the module.
     */
    public function getUniqueId()
    {
        return $this->module ? ltrim($this->module->getUniqueId() . '/' . $this->id, '/') : $this->id;
    }

    /**
     * 获取模块根目录
     * Returns the root directory of the module.
     * It defaults to the directory containing the module class file.
     * @return string the root directory of the module.
     */
    public function getBasePath()
    {
        $class = new \ReflectionClass($this);
        // 获取的是调用者所在文件的路径
        // D:\ding\wamp64\www\learn\yii\yiilearn\basic\modules\ding
        $basePath = dirname($class->getFileName());
        // 获取的是该文件的绝对路径
        // /Users/echo-ding/Documents/ding/www/yii/learn/yiilearn/basic/vendor/yiisoft/yii2/base
        // echo $basePath = __DIR__;

        if ($this->_basePath === null) {
            // 反射获取文件目录
            $class = new \ReflectionClass($this);
            $this->_basePath = dirname($class->getFileName());
        }

        return $this->_basePath;
    }

    /**
     * 设置根目录
     * Sets the root directory of the module.
     * This method can only be invoked at the beginning of the constructor.
     * @param string $path the root directory of the module. This can be either a directory name or a [path alias](guide:concept-aliases).
     * @throws InvalidParamException if the directory does not exist.
     */
    public function setBasePath($path)
    {
        $path = Yii::getAlias($path);
        $p = strncmp($path, 'phar://', 7) === 0 ? $path : realpath($path);
        // 没找到为什么
        //$path = D:\ding\wamp64\www\learn\yii\yiilearn\basic;进入到 $ding = $path;
        if (strncmp($path, 'phar://', 7) === 0) {
            $ding = $path;
        }else {
            $ding = realpath($path);
        }
        if ($p !== false && is_dir($p)) {
            $this->_basePath = $p;
        } else {
            throw new InvalidParamException("The directory does not exist: $path");
        }
    }

    /**
     * 获取该模块下控制器的路径
     * Returns the directory that contains the controller classes according to [[controllerNamespace]].
     * Note that in order for this method to return a value, you must define
     * an alias for the root namespace of [[controllerNamespace]].
     * @return string the directory that contains the controller classes.
     * @throws InvalidParamException if there is no alias defined for the root namespace of [[controllerNamespace]].
     */
    public function getControllerPath()
    {
        return Yii::getAlias('@' . str_replace('\\', '/', $this->controllerNamespace));
    }

    /**
     * 获取该模块下view视图的路径
     * Returns the directory that contains the view files for this module.
     * @return string the root directory of view files. Defaults to "[[basePath]]/views".
     */
    public function getViewPath()
    {
        if ($this->_viewPath === null) {
            $this->_viewPath = $this->getBasePath() . DIRECTORY_SEPARATOR . 'views';
        }
        return $this->_viewPath;
    }

    /**
     * Sets the directory that contains the view files.
     * @param string $path the root directory of view files.
     * @throws InvalidParamException if the directory is invalid.
     */
    public function setViewPath($path)
    {
        $this->_viewPath = Yii::getAlias($path);
    }

    /**
     * 该模块下layouts的路径
     * Returns the directory that contains layout view files for this module.
     * @return string the root directory of layout files. Defaults to "[[viewPath]]/layouts".
     */
    public function getLayoutPath()
    {
        if ($this->_layoutPath === null) {
            $this->_layoutPath = $this->getViewPath() . DIRECTORY_SEPARATOR . 'layouts';
        }

        return $this->_layoutPath;
    }

    /**
     * Sets the directory that contains the layout files.
     * @param string $path the root directory or [path alias](guide:concept-aliases) of layout files.
     * @throws InvalidParamException if the directory is invalid
     */
    public function setLayoutPath($path)
    {
        $this->_layoutPath = Yii::getAlias($path);
    }

    /**
     * Returns current module version.
     * If version is not explicitly set, [[defaultVersion()]] method will be used to determine its value.
     * @return string the version of this module.
     * @since 2.0.11
     */
    public function getVersion()
    {
        if ($this->_version === null) {
            $this->_version = $this->defaultVersion();
        } else {
            if (!is_scalar($this->_version)) {
                $this->_version = call_user_func($this->_version, $this);
            }
        }
        return $this->_version;
    }

    /**
     * Sets current module version.
     * @param string|callable $version the version of this module.
     * Version can be specified as a PHP callback, which can accept module instance as an argument and should
     * return the actual version. For example:
     *
     * ```php
     * function (Module $module) {
     *     //return string
     * }
     * ```
     *
     * @since 2.0.11
     */
    public function setVersion($version)
    {
        $this->_version = $version;
    }

    /**
     *
     * Returns default module version.
     * Child class may override this method to provide more specific version detection.
     * @return string the version of this module.
     * @since 2.0.11
     */
    protected function defaultVersion()
    {
        if ($this->module === null) {
            return '1.0';
        }
        return $this->module->getVersion();
    }

    /**
     * 设置别名
     * Defines path aliases.
     * This method calls [[Yii::setAlias()]] to register the path aliases.
     * This method is provided so that you can define path aliases when configuring a module.
     * @property array list of path aliases to be defined. The array keys are alias names
     * (must start with `@`) and the array values are the corresponding paths or aliases.
     * See [[setAliases()]] for an example.
     * @param array $aliases list of path aliases to be defined. The array keys are alias names
     * (must start with `@`) and the array values are the corresponding paths or aliases.
     * For example,
     *
     * ```php
     * [
     *     '@models' => '@app/models', // an existing alias
     *     '@backend' => __DIR__ . '/../backend',  // a directory
     * ]
     * ```
     */
    public function setAliases($aliases)
    {
        foreach ($aliases as $name => $alias) {
            Yii::setAlias($name, $alias);
        }
    }

    /**
     * 检查是否存在module 递归检查多级的
     * Checks whether the child module of the specified ID exists.
     * This method supports checking the existence of both child and grand child modules.
     * @param string $id module ID. For grand child modules, use ID path relative to this module (e.g. `admin/content`).
     * @return bool whether the named module exists. Both loaded and unloaded modules
     * are considered.
     */
    public function hasModule($id)
    {
        // 检查是否存在 / 也就是多级模块
        if (($pos = strpos($id, '/')) !== false) {
            // sub-module
            $module = $this->getModule(substr($id, 0, $pos));

            return $module === null ? false : $module->hasModule(substr($id, $pos + 1));
        }
        return isset($this->_modules[$id]);
    }

    /**
     * 获取模块对象，可以是多级模块
     * 返回最后一个module
     * 例如 ding/ran/bunao
     * 返回的是bunao模块对象
     * 在创建的同时又会检查各个模块是否已经在配置中配置，假设，bunao模块没有配置，将会返回null
     *
     * Retrieves the child module of the specified ID.
     * This method supports retrieving both child modules and grand child modules.
     * @param string $id module ID (case-sensitive). To retrieve grand child modules,
     * use ID path relative to this module (e.g. `admin/content`).
     * @param bool $load whether to load the module if it is not yet loaded.
     * @return Module|null the module instance, `null` if the module does not exist.
     * @see hasModule()
     */
    public function getModule($id, $load = true)
    {
        //多级， 递归创建
        if (($pos = strpos($id, '/')) !== false) {
            // sub-module
            $module = $this->getModule(substr($id, 0, $pos));

            return $module === null ? null : $module->getModule(substr($id, $pos + 1), $load);
        }
        // 配置的module模块,或在module中配置的子模块
        if (isset($this->_modules[$id])) {
            if ($this->_modules[$id] instanceof Module) {
                return $this->_modules[$id];
            } elseif ($load) {
                Yii::trace("Loading module: $id", __METHOD__);
                //创建模块 并通过构造函数配置 $id $this
                /* @var $module Module */
                $module = Yii::createObject($this->_modules[$id], [$id, $this]);
                // 放进Yii::$app->loadedModules 数组中
                $module->setInstance($module);
                return $this->_modules[$id] = $module;
            }
        }

        return null;
    }

    /**
     *
     * Adds a sub-module to this module.
     * @param string $id module ID.
     * @param Module|array|null $module the sub-module to be added to this module. This can
     * be one of the following:
     *
     * - a [[Module]] object
     * - a configuration array: when [[getModule()]] is called initially, the array
     *   will be used to instantiate the sub-module
     * - `null`: the named sub-module will be removed from this module
     */
    public function setModule($id, $module)
    {
        if ($module === null) {
            unset($this->_modules[$id]);
        } else {
            $this->_modules[$id] = $module;
        }
    }

    /**
     * Returns the sub-modules in this module.
     * @param bool $loadedOnly whether to return the loaded sub-modules only. If this is set `false`,
     * then all sub-modules registered in this module will be returned, whether they are loaded or not.
     * Loaded modules will be returned as objects, while unloaded modules as configuration arrays.
     * @return array the modules (indexed by their IDs).
     */
    public function getModules($loadedOnly = false)
    {
        if ($loadedOnly) {
            $modules = [];
            foreach ($this->_modules as $module) {
                if ($module instanceof Module) {
                    $modules[] = $module;
                }
            }

            return $modules;
        }
        return $this->_modules;
    }

    /**
     * 配置在配置文件中的将会在这里进行赋值
     * Registers sub-modules in the current module.
     *
     * Each sub-module should be specified as a name-value pair, where
     * name refers to the ID of the module and value the module or a configuration
     * array that can be used to create the module. In the latter case, [[Yii::createObject()]]
     * will be used to create the module.
     *
     * If a new sub-module has the same ID as an existing one, the existing one will be overwritten silently.
     *
     * The following is an example for registering two sub-modules:
     *
     * ```php
     * [
     *     'comment' => [
     *         'class' => 'app\modules\comment\CommentModule',
     *         'db' => 'db',
     *     ],
     *     'booking' => ['class' => 'app\modules\booking\BookingModule'],
     * ]
     * ```
     *
     * @param array $modules modules (id => module configuration or instances).
     */
    public function setModules($modules)
    {
        foreach ($modules as $id => $module) {
            $this->_modules[$id] = $module;
        }
    }

    /**
     * 执行路由的动作
     * 
     * Runs a controller action specified by a route.
     * This method parses the specified route and creates the corresponding child module(s), controller and action
     * instances. It then calls [[Controller::runAction()]] to run the action with the given parameters.
     * If the route is empty, the method will use [[defaultRoute]].
     * @param string $route the route that specifies the action. 路由
     * @param array $params the parameters to be passed to the action action action的参数
     * @return mixed the result of the action.
     * @throws InvalidRouteException if the requested route cannot be resolved into an action successfully.
     */
    public function runAction($route, $params = [])
    {
        // 根据路由创建控制器，会找到最后一层的module，并根据此module来创建控制器
        $parts = $this->createController($route);
        if (is_array($parts)) {
            /* @var $controller Controller */
            list($controller, $actionID) = $parts;
            // 暂用Yii::$app->controller 
            // 这种情况应该就是控制器里调用runAction
            $oldController = Yii::$app->controller;
            Yii::$app->controller = $controller;
            $result = $controller->runAction($actionID, $params);
            if ($oldController !== null) {
                Yii::$app->controller = $oldController;
            }

            return $result;
        }
        // 获取路径
        $id = $this->getUniqueId();
        throw new InvalidRouteException('Unable to resolve the request "' . ($id === '' ? $route : $id . '/' . $route) . '".');
    }

    /**
     * 创建一个控制器实例根据路由
     * Creates a controller instance based on the given route.
     *
     * The route should be relative to this module. The method implements the following algorithm
     * to resolve the given route:
     *
     * 1. If the route is empty, use [[defaultRoute]];
     * 2. If the first segment of the route is a valid module ID as declared in [[modules]],
     *    call the module's `createController()` with the rest part of the route;
     * 3. If the first segment of the route is found in [[controllerMap]], create a controller
     *    based on the corresponding configuration found in [[controllerMap]];
     * 4. The given route is in the format of `abc/def/xyz`. Try either `abc\DefController`
     *    or `abc\def\XyzController` class within the [[controllerNamespace|controller namespace]].
     *
     * If any of the above steps resolves into a controller, it is returned together with the rest
     * part of the route which will be treated as the action ID. Otherwise, `false` will be returned.
     *
     * @param string $route the route consisting of module, controller and action IDs.
     * @return array|bool If the controller is created successfully, it will be returned together
     * with the requested action ID. Otherwise `false` will be returned.
     * @throws InvalidConfigException if the controller class and its file do not match.
     */
    public function createController($route)
    {
        // 默认路由
        if ($route === '') {
            $route = $this->defaultRoute;
        }

        // double slashes or leading/ending slashes may cause substr problem
        $route = trim($route, '/');
        // 如果存在 // 格式错误的
        if (strpos($route, '//') !== false) {
            return false;
        }

        if (strpos($route, '/') !== false) {
            // 根据 /分割成两个 如：api/site/index 分割成 api aite/index
            list ($id, $route) = explode('/', $route, 2);
        } else {
            // 如果省略了 action
            $id = $route;
            $route = '';
        }
        // 是否存在定义在module上的控制器映射中
        // module and controller map take precedence
        if (isset($this->controllerMap[$id])) {
            $controller = Yii::createObject($this->controllerMap[$id], [$id, $this]);
            return [$controller, $route];
        }
        // 看是否存在module 返回的是
        $module = $this->getModule($id);
        if ($module !== null) {
            // 通过此 Modules创建控制器，递归调用，可以有多个module嵌套
            return $module->createController($route);
        }
        // module层已经处理创建完成
        // / 最后一次出现的位置
        // 控制器 控制器文件夹 controllers中又创建了一个文件夹，请求里层文件夹内的控制器
        if (($pos = strrpos($route, '/')) !== false) {
            $id .= '/' . substr($route, 0, $pos);
            $route = substr($route, $pos + 1);
        }
        // 获取控制器
        $controller = $this->createControllerByID($id);
        // 如果没有写 action
        if ($controller === null && $route !== '') {
            $controller = $this->createControllerByID($id . '/' . $route);
            $route = '';
        }

        return $controller === null ? false : [$controller, $route];
    }

    /**
     * 创建控制器根据id
     * Creates a controller based on the given controller ID.
     *
     * The controller ID is relative to this module. The controller class
     * should be namespaced under [[controllerNamespace]].
     *
     * Note that this method does not check [[modules]] or [[controllerMap]].
     *
     * @param string $id the controller ID.
     * @return Controller|null the newly created controller instance, or `null` if the controller ID is invalid.
     * @throws InvalidConfigException if the controller class and its file name do not match.
     * This exception is only thrown when in debug mode.
     */
    public function createControllerByID($id)
    {
        // 如果存在 / 则获取最后一个 / 之后的作为类名
        $pos = strrpos($id, '/');
        if ($pos === false) {
            $prefix = '';
            $className = $id;
        } else {
            $prefix = substr($id, 0, $pos + 1);
            $className = substr($id, $pos + 1);
        }
        // 过滤不合格的类名
        if (!preg_match('%^[a-z][a-z0-9\\-_]*$%', $className)) {
            return null;
        }
        if ($prefix !== '' && !preg_match('%^[a-z0-9_/]+$%i', $prefix)) {
            return null;
        }
        // 拼接控制器名
        // 把 ding-ran 转换成 ding ran 再转换成 Ding Ran 在转换成 DingRan 再拼接 DingRanController
        $className = str_replace(' ', '', ucwords(str_replace('-', ' ', $className))) . 'Controller';
        // 拼接控制器路径
        $className = ltrim($this->controllerNamespace . '\\' . str_replace('/', '\\', $prefix)  . $className, '\\');
        // 转换后如果还包含 - 或 类不存在(会自动调用autoload)
        if (strpos($className, '-') !== false || !class_exists($className)) {
            return null;
        }
        // 是否是 'yii\base\Controller' 的子类
        if (is_subclass_of($className, 'yii\base\Controller')) {
            // 创建控制器 保存 id 和 module
            $controller = Yii::createObject($className, [$id, $this]);
            return get_class($controller) === $className ? $controller : null;
        } elseif (YII_DEBUG) {
            throw new InvalidConfigException("Controller class must extend from \\yii\\base\\Controller.");
        }
        return null;
    }

    /**
     * 执行Action 的操作
     * This method is invoked right before an action within this module is executed.
     *
     * The method will trigger the [[EVENT_BEFORE_ACTION]] event. The return value of the method
     * will determine whether the action should continue to run.
     *
     * In case the action should not run, the request should be handled inside of the `beforeAction` code
     * by either providing the necessary output or redirecting the request. Otherwise the response will be empty.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function beforeAction($action)
     * {
     *     if (!parent::beforeAction($action)) {
     *         return false;
     *     }
     *
     *     // your custom code here
     *
     *     return true; // or false to not run the action
     * }
     * ```
     *
     * @param Action $action the action to be executed.   需要执行的 action对象
     * @return bool whether the action should continue to be executed.
     */
    public function beforeAction($action)
    {
        $event = new ActionEvent($action);
        $this->trigger(self::EVENT_BEFORE_ACTION, $event);
        return $event->isValid;
    }

    /**
     * This method is invoked right after an action within this module is executed.
     *
     * The method will trigger the [[EVENT_AFTER_ACTION]] event. The return value of the method
     * will be used as the action return value.
     *
     * If you override this method, your code should look like the following:
     *
     * ```php
     * public function afterAction($action, $result)
     * {
     *     $result = parent::afterAction($action, $result);
     *     // your custom code here
     *     return $result;
     * }
     * ```
     *
     * @param Action $action the action just executed.
     * @param mixed $result the action return result.
     * @return mixed the processed action result.
     */
    public function afterAction($action, $result)
    {
        $event = new ActionEvent($action);
        $event->result = $result;
        $this->trigger(self::EVENT_AFTER_ACTION, $event);
        return $event->result;
    }
}
