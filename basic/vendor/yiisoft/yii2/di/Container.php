<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\di;

use ReflectionClass;
use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
/*
依赖注入容器，解决依赖问题
 */
/**
 *
 * 示例
 *
 *
 * namespace app\models;
 *
 * use yii\base\Object;
 * use yii\db\Connection;
 * use yii\di\Container;
 *
 * interface UserFinderInterface
 * {
 *     function findUser();
 * }
 *
 * class UserFinder extends Object implements UserFinderInterface
 * {
 *     public $db;
 *
 *     public function __construct(Connection $db, $config = [])
 *     {
 *         $this->db = $db;
 *         parent::__construct($config);
 *     }
 *
 *     public function findUser()
 *     {
 *     }
 * }
 *
 * class UserLister extends Object
 * {
 *     public $finder;
 *
 *     public function __construct(UserFinderInterface $finder, $config = [])
 *     {
 *         $this->finder = $finder;
 *         parent::__construct($config);
 *     }
 * }
 *
 * $container = new Container;
 * $container->set('yii\db\Connection', [
 *     'dsn' => '...',
 * ]);
 * $container->set('app\models\UserFinderInterface', [
 *     'class' => 'app\models\UserFinder',
 * ]);
 * $container->set('userLister', 'app\models\UserLister');
 *
 * $lister = $container->get('userLister');
 *
 * 和下面的相同
 * // which is equivalent to:
 *
 * $db = new \yii\db\Connection(['dsn' => '...']);
 * $finder = new UserFinder($db);
 * $lister = new UserLister($finder);
 *
 */
/**
 * Container implements a [dependency injection](http://en.wikipedia.org/wiki/Dependency_injection) container.
 *
 * A dependency injection (DI) container is an object that knows how to instantiate and configure objects and
 * all their dependent objects. For more information about DI, please refer to
 * [Martin Fowler's article](http://martinfowler.com/articles/injection.html).
 *
 * Container supports constructor injection as well as property injection.
 *
 * To use Container, you first need to set up the class dependencies by calling [[set()]].
 * You then call [[get()]] to create a new class object. Container will automatically instantiate
 * dependent objects, inject them into the object being created, configure and finally return the newly created object.
 *
 * By default, [[\Yii::$container]] refers to a Container instance which is used by [[\Yii::createObject()]]
 * to create new object instances. You may use this method to replace the `new` operator
 * when creating a new object, which gives you the benefit of automatic dependency resolution and default
 * property configuration.
 *
 * Below is an example of using Container:
 *
 * ```php
 * namespace app\models;
 *
 * use yii\base\Object;
 * use yii\db\Connection;
 * use yii\di\Container;
 *
 * interface UserFinderInterface
 * {
 *     function findUser();
 * }
 *
 * class UserFinder extends Object implements UserFinderInterface
 * {
 *     public $db;
 *
 *     public function __construct(Connection $db, $config = [])
 *     {
 *         $this->db = $db;
 *         parent::__construct($config);
 *     }
 *
 *     public function findUser()
 *     {
 *     }
 * }
 *
 * class UserLister extends Object
 * {
 *     public $finder;
 *
 *     public function __construct(UserFinderInterface $finder, $config = [])
 *     {
 *         $this->finder = $finder;
 *         parent::__construct($config);
 *     }
 * }
 *
 * $container = new Container;
 * $container->set('yii\db\Connection', [
 *     'dsn' => '...',
 * ]);
 * $container->set('app\models\UserFinderInterface', [
 *     'class' => 'app\models\UserFinder',
 * ]);
 * $container->set('userLister', 'app\models\UserLister');
 *
 * $lister = $container->get('userLister');
 *
 * // which is equivalent to:
 *
 * $db = new \yii\db\Connection(['dsn' => '...']);
 * $finder = new UserFinder($db);
 * $lister = new UserLister($finder);
 * ```
 *
 * For more details and usage information on Container, see the [guide article on di-containers](guide:concept-di-container).
 *
 * @property array $definitions The list of the object definitions or the loaded shared objects (type or ID =>
 * definition or instance). This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Container extends Component
{
    /**
     * 用于保存单例Singleton对象，以对象类型为键
     * @var array singleton objects indexed by their types
     */
    private $_singletons = [];
    /**
     * 用于保存依赖的定义，以对象类型为键  set 的时候保存的
     * @var array object definitions indexed by their types
     */
    private $_definitions = [];
    /**
     * 用于保存构造函数的参数，以对象类型为键
     * @var array constructor parameters indexed by object types
     */
    private $_params = [];
    /**
     * 用于缓存ReflectionClass(反射)对象，以类名或接口名为键
     * @var array cached ReflectionClass objects indexed by class/interface names
     */
    private $_reflections = [];
    /**
     * 用于缓存依赖信息，以类名或接口名为键  get获取解析的时候保存的
     * @var array cached dependencies indexed by class/interface names. Each class name
     * is associated with a list of constructor parameter types or default values.
     */
    private $_dependencies = [];


    /**
     * Returns an instance of the requested class.
     *
     * You may provide constructor parameters (`$params`) and object configurations (`$config`)
     * that will be used during the creation of the instance.
     *
     * If the class implements [[\yii\base\Configurable]], the `$config` parameter will be passed as the last
     * parameter to the class constructor; Otherwise, the configuration will be applied *after* the object is
     * instantiated.
     *
     * Note that if the class is declared to be singleton by calling [[setSingleton()]],
     * the same instance of the class will be returned each time this method is called.
     * In this case, the constructor parameters and object configurations will be used
     * only if the class is instantiated the first time.
     *
     * @param string $class the class name or an alias name (e.g. `foo`) that was previously registered via [[set()]]
     * or [[setSingleton()]].
     * @param array $params a list of constructor parameter values. The parameters should be provided in the order
     * they appear in the constructor declaration. If you want to skip some parameters, you should index the remaining
     * ones with the integers that represent their positions in the constructor parameter list.
     * @param array $config a list of name-value pairs that will be used to initialize the object properties.
     * @return object an instance of the requested class.
     * @throws InvalidConfigException if the class cannot be recognized or correspond to an invalid definition
     * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
     */
    /**
     * 获取实例，自动解析依赖
     * @param  [type] $class  类名或者别名
     * @param  array  $params 构造方法的参数值，要按顺序来 []
     * @param  array  $config 要给实例属性赋值的配置数组 如 ：['id' => 11];
     * @return [type]         [description]
     */
    public function get($class, $params = [], $config = [])
    {
        // 如果已经有了单例对象，则直接返回创建好的单例对象
        if (isset($this->_singletons[$class])) {
            return $this->_singletons[$class];
        // 如果没有注册依赖而直接get()
        } elseif (!isset($this->_definitions[$class])) {
            // 创建对象,$class必须是类，不能是别名
            return $this->build($class, $params, $config);
        }
        // 获取依赖信息
        $definition = $this->_definitions[$class];
        // 依赖是匿名函数
        if (is_callable($definition, true)) {
            // 合并参数,实例化参数的依赖
            $params = $this->resolveDependencies($this->mergeParams($class, $params));
            // 把第一个参数作为回调函数调用
            // 回调函数 $definition 要接收三个参数
            $object = call_user_func($definition, $this, $params, $config);
        // 如果是数组，必定还有 class 因为 set的时候依赖进行了标准化
        } elseif (is_array($definition)) {
            $concrete = $definition['class'];
            unset($definition['class']);
            // 合并属性配置
            $config = array_merge($definition, $config);
            // 合并 $class 构造函数参数配置值
            $params = $this->mergeParams($class, $params);
            // 依赖如果和自己相等
            if ($concrete === $class) {
                // 创建对象
                $object = $this->build($class, $params, $config);
            } else {
                // 递归获取依赖
                $object = $this->get($concrete, $params, $config);
            }
        // 如果依赖是一个对象，添加到单利数组
        } elseif (is_object($definition)) {
            return $this->_singletons[$class] = $definition;
        } else {
            throw new InvalidConfigException('Unexpected object definition type: ' . gettype($definition));
        }
        // 存入单利数组 _singletons
        if (array_key_exists($class, $this->_singletons)) {
            // singleton
            $this->_singletons[$class] = $object;
        }

        return $object;
    }

    /**
     * 注册依赖
     * Registers a class definition with this container.
     *
     * For example,
     *
     * ```php
     * // register a class name as is. This can be skipped.
     * $container->set('yii\db\Connection');
     *
     * // register an interface
     * // When a class depends on the interface, the corresponding class
     * // will be instantiated as the dependent object
     * $container->set('yii\mail\MailInterface', 'yii\swiftmailer\Mailer');
     *
     * // register an alias name. You can use $container->get('foo')
     * // to create an instance of Connection
     * $container->set('foo', 'yii\db\Connection');
     *
     * // register a class with configuration. The configuration
     * // will be applied when the class is instantiated by get()
     * $container->set('yii\db\Connection', [
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // register an alias name with class configuration
     * // In this case, a "class" element is required to specify the class
     * $container->set('db', [
     *     'class' => 'yii\db\Connection',
     *     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
     *     'username' => 'root',
     *     'password' => '',
     *     'charset' => 'utf8',
     * ]);
     *
     * // register a PHP callable
     * // The callable will be executed when $container->get('db') is called
     * $container->set('db', function ($container, $params, $config) {
     *     return new \yii\db\Connection($config);
     * });
     * ```
     *
     * If a class definition with the same name already exists, it will be overwritten with the new one.
     * You may use [[has()]] to check if a class definition already exists.
     *
     * @param string $class class name, interface name or alias name
     * @param mixed $definition the definition associated with `$class`. It can be one of the following:
     *
     * - a PHP callable: The callable will be executed when [[get()]] is invoked. The signature of the callable
     *   should be `function ($container, $params, $config)`, where `$params` stands for the list of constructor
     *   parameters, `$config` the object configuration, and `$container` the container object. The return value
     *   of the callable will be returned by [[get()]] as the object instance requested.
     * - a configuration array: the array contains name-value pairs that will be used to initialize the property
     *   values of the newly created object when [[get()]] is called. The `class` element stands for the
     *   the class of the object to be created. If `class` is not specified, `$class` will be used as the class name.
     * - a string: a class name, an interface name or an alias name.
     * @param array $params the list of constructor parameters. The parameters will be passed to the class
     * constructor when [[get()]] is called.
     * @return $this the container itself
     */


// =======================================================
// 示例
// $container = new \yii\di\Container;

// // 直接以类名注册一个依赖，虽然这么做没什么意义。
// $container->set('yii\db\Connection');
// // $_definition['yii\db\Connection'] = ['class' => 'yii\db\Connetcion']


// // 注册一个接口，当一个类依赖于该接口时，注册的接口对应的类会自动被实例化，并供有依赖需要的类使用。
// $container->set('yii\mail\MailInterface', 'yii\swiftmailer\Mailer');
// // $_definition['yii\mail\MailInterface'] = ['class' => 'yii\swiftmailer\Mailer']

// // 注册一个别名，当调用$container->get('foo')时，可以得到一个 yii\db\Connection 实例。
// $container->set('foo', 'yii\db\Connection');
// // $_definition['foo'] = ['class' => 'yii\db\Connection']

// // 用一个配置数组来注册一个类，需要这个类的实例时，这个配置数组会发生作用。
// $container->set('yii\db\Connection', [
//     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
//     'username' => 'root',
//     'password' => '',
//     'charset' => 'utf8',
// ]);
// $_definition['yii\db\Connection'] = [
//    'class' => 'yii\db\Connection',
//    'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
//    'username' => 'root',
//    'password' => '',
//    'charset' => 'utf8',
// ];

// // 用一个配置数组来注册一个别名，由于别名的类型不详，因此配置数组中需要有 class 元素。
// $container->set('db', [
//     'class' => 'yii\db\Connection',
//     'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
//     'username' => 'root',
//     'password' => '',
//     'charset' => 'utf8',
// ]);

// /*$_definition['db'] = [
//    'class' => 'yii\db\Connection',
//    'dsn' => 'mysql:host=127.0.0.1;dbname=demo',
//    'username' => 'root',
//    'password' => '',
//    'charset' => 'utf8',
// ];*/
// // 用一个PHP callable来注册一个别名，每次引用这个别名时，这个callable都会被调用。
// $container->set('db', function ($container, $params, $config) {
//     return new \yii\db\Connection($config);
// });
// // $_definition['db'] = function(...){...}

// // 用一个对象来注册一个别名，每次引用这个别名时，这个对象都会被引用。
// $container->set('pageCache', new FileCache);
// // $_definition['pageCache'] = an InstanceOf FileCache 一个FileCache的实例



// =======================================================

    /**
     * 注册依赖
     * @param [type] $class      类名/接口名/别名
     * @param array  $definition 依赖
     * @param array  $params     参数
     */
    public function set($class, $definition = [], array $params = [])
    {
        // 将规范后的依赖存入依赖数组 _definitions
        $this->_definitions[$class] = $this->normalizeDefinition($class, $definition);
        // 存入 参数数组
        $this->_params[$class] = $params;
        // 删除
        unset($this->_singletons[$class]);
        return $this;
    }

    /**
     * Registers a class definition with this container and marks the class as a singleton class.
     *
     * This method is similar to [[set()]] except that classes registered via this method will only have one
     * instance. Each time [[get()]] is called, the same instance of the specified class will be returned.
     *
     * @param string $class class name, interface name or alias name
     * @param mixed $definition the definition associated with `$class`. See [[set()]] for more details.
     * @param array $params the list of constructor parameters. The parameters will be passed to the class
     * constructor when [[get()]] is called.
     * @return $this the container itself
     * @see set()
     */
    public function setSingleton($class, $definition = [], array $params = [])
    {
        // 将规范后的依赖存入依赖数组 _definitions
        $this->_definitions[$class] = $this->normalizeDefinition($class, $definition);
        // 存入 参数数组
        $this->_params[$class] = $params;
        // 设置为null
        $this->_singletons[$class] = null;
        return $this;
    }

    /**
     * 容器是否存在
     * Returns a value indicating whether the container has the definition of the specified name.
     * @param string $class class name, interface name or alias name
     * @return bool whether the container has the definition of the specified name..
     * @see set()
     */
    public function has($class)
    {
        return isset($this->_definitions[$class]);
    }

    /**
     * 是否存在
     * Returns a value indicating whether the given name corresponds to a registered singleton.
     * @param string $class class name, interface name or alias name
     * @param bool $checkInstance whether to check if the singleton has been instantiated.
     * @return bool whether the given name corresponds to a registered singleton. If `$checkInstance` is true,
     * the method should return a value indicating whether the singleton has been instantiated.
     */
    public function hasSingleton($class, $checkInstance = false)
    {
        return $checkInstance ? isset($this->_singletons[$class]) : array_key_exists($class, $this->_singletons);
    }

    /**
     * 清空容器
     * Removes the definition for the specified name.
     * @param string $class class name, interface name or alias name
     */
    public function clear($class)
    {
        unset($this->_definitions[$class], $this->_singletons[$class]);
    }

    /**
     * 规范依赖类的定义
     * Normalizes the class definition.
     * @param string $class class name    类名
     * @param string|array|callable $definition the class definition  定义的类
     * @return array the normalized class definition
     * @throws InvalidConfigException if the definition is invalid.
     */
    protected function normalizeDefinition($class, $definition)
    {
        // 没有定义类
        if (empty($definition)) {
            return ['class' => $class];
        // 字符串也直接为类
        } elseif (is_string($definition)) {
            return ['class' => $definition];
        // 回调函数或者是对象
        } elseif (is_callable($definition, true) || is_object($definition)) {
            return $definition;
        // 数组
        } elseif (is_array($definition)) {
            // 没有定义类
            if (!isset($definition['class'])) {
                //
                if (strpos($class, '\\') !== false) {
                    $definition['class'] = $class;
                } else {
                    throw new InvalidConfigException("A class definition requires a \"class\" member.");
                }
            }
            return $definition;
        } else {
            throw new InvalidConfigException("Unsupported definition type for \"$class\": " . gettype($definition));
        }
    }

    /**
     * Returns the list of the object definitions or the loaded shared objects.
     * @return array the list of the object definitions or the loaded shared objects (type or ID => definition or instance).
     */
    public function getDefinitions()
    {
        return $this->_definitions;
    }

    /**
     * 创建实例
     * Creates an instance of the specified class.
     * This method will resolve dependencies of the specified class, instantiate them, and inject
     * them into the new instance of the specified class.
     * @param string $class the class name 类名
     * @param array $params constructor parameters 构造函数参数
     * @param array $config configurations to be applied to the new instance 属性配置值
     * @return object the newly created instance of the specified class
     * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
     */
    protected function build($class, $params, $config)
    {
        // 反射对象 参数依赖信息
        /* @var $reflection ReflectionClass */
        list ($reflection, $dependencies) = $this->getDependencies($class);
        // 给构造参数赋值
        foreach ($params as $index => $param) {
            $dependencies[$index] = $param;
        }
        // 递归解析依赖，实例化构造参数依赖对象
        $dependencies = $this->resolveDependencies($dependencies, $reflection);
        // 检查类是否可实例化, 排除抽象类abstract和对象接口interface
        if (!$reflection->isInstantiable()) {
            throw new NotInstantiableException($reflection->name);
        }
        // 通过反射传递构造参数实例化对象
        if (empty($config)) {
            return $reflection->newInstanceArgs($dependencies);
        }
        // 检查它是否实现了一个Configurable接口，也就是继承自Object对象
        if (!empty($dependencies) && $reflection->implementsInterface('yii\base\Configurable')) {
            // 继承object后，规则上，是要在构造函数中最后一个参数和Object中的一直，并调用Object中的构造函数
            // set $config as the last parameter (existing one will be overwritten)
            $dependencies[count($dependencies) - 1] = $config;
            return $reflection->newInstanceArgs($dependencies);
        } else {
            // 创建对象并给属性赋值
            $object = $reflection->newInstanceArgs($dependencies);
            foreach ($config as $name => $value) {
                $object->$name = $value;
            }
            return $object;
        }
    }

    /**
     * 合并参数
     * Merges the user-specified constructor parameters with the ones registered via [[set()]].
     * @param string $class class name, interface name or alias name
     * @param array $params the constructor parameters
     * @return array the merged parameters
     */
    protected function mergeParams($class, $params)
    {
        if (empty($this->_params[$class])) {
            return $params;
        } elseif (empty($params)) {
            return $this->_params[$class];
        } else {
            $ps = $this->_params[$class];
            foreach ($params as $index => $value) {
                $ps[$index] = $value;
            }
            return $ps;
        }
    }

    /**
     * 返回依赖项
     * Returns the dependencies of the specified class.
     * @param string $class class name, interface name or alias name 不能是别名
     * @return array the dependencies of the specified class.
     */
    protected function getDependencies($class)
    {
        // 如果已经通过此类解析过此类的构造方法，直接返回
        if (isset($this->_reflections[$class])) {
            return [$this->_reflections[$class], $this->_dependencies[$class]];
        }

        $dependencies = [];
        // 反射 这就要求不能是别名，别名会导致出错
        $reflection = new ReflectionClass($class);
        // 获取构造函数
        $constructor = $reflection->getConstructor();
        if ($constructor !== null) {
            // 遍历构造函数参数
            foreach ($constructor->getParameters() as $param) {
                // 是否有有效的默认值 假设 ding($d = 'a', $b) $d就不是有效的默认值，必须要赋值的
                if ($param->isDefaultValueAvailable()) {
                    // 默认值作为依赖，既然有默认值，就肯定是基本类型，也就不需要依赖了
                    $dependencies[] = $param->getDefaultValue();
                // 没默认值的
                } else {
                    // 获取强制类型的类，如 function ding(RanClass ran)  获取Ranclass
                    // 如果是基本类型则获取到的是 null
                    $c = $param->getClass();
                    // 创建Instance实例
                    $dependencies[] = Instance::of($c === null ? null : $c->getName());
                }
            }
        }
        // 存入反射数组
        $this->_reflections[$class] = $reflection;
        // 存入缓存依赖数组
        $this->_dependencies[$class] = $dependencies;

        return [$reflection, $dependencies];
    }

    /**
     * 解析构造函数参数的依赖
     * Resolves dependencies by replacing them with the actual object instances.
     * @param array $dependencies the dependencies  构造参数的依赖信息
     * @param ReflectionClass $reflection the class reflection associated with the dependencies 反射对象
     * @return array the resolved dependencies
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     */
    protected function resolveDependencies($dependencies, $reflection = null)
    {
        foreach ($dependencies as $index => $dependency) {
            // 如果参数是没有默认值
            if ($dependency instanceof Instance) {
                // 如果参数是类类型
                if ($dependency->id !== null) {
                    // 获取对象，解析依赖
                    $dependencies[$index] = $this->get($dependency->id);
                // 如果存在反射对象，获取信息抛出错误
                // 不存在依赖还不给赋值，报错了
                } elseif ($reflection !== null) {
                    $name = $reflection->getConstructor()->getParameters()[$index]->getName();
                    $class = $reflection->getName();
                    throw new InvalidConfigException("Missing required parameter \"$name\" when instantiating \"$class\".");
                }
            }
        }
        return $dependencies;
    }

    /**
     * 解析 函数/方法 参数依赖
     * Invoke a callback with resolving dependencies in parameters.
     *
     * This methods allows invoking a callback and let type hinted parameter names to be
     * resolved as objects of the Container. It additionally allow calling function using named parameters.
     *
     * For example, the following callback may be invoked using the Container to resolve the formatter dependency:
     *
     * ```php
     * $formatString = function($string, \yii\i18n\Formatter $formatter) {
     *    // ...
     * }
     * Yii::$container->invoke($formatString, ['string' => 'Hello World!']);
     * ```
     *
     * This will pass the string `'Hello World!'` as the first param, and a formatter instance created
     * by the DI container as the second param to the callable.
     *
     * @param callable $callback callable to be invoked.
     * @param array $params The array of parameters for the function.
     * This can be either a list of parameters, or an associative array representing named function parameters.
     * @return mixed the callback return value.
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
     * @since 2.0.7
     */
    public function invoke(callable $callback, $params = [])
    {
        if (is_callable($callback)) {
            return call_user_func_array($callback, $this->resolveCallableDependencies($callback, $params));
        } else {
            return call_user_func_array($callback, $params);
        }
    }

    /**
     * 解析函数/方法的依赖
     * Resolve dependencies for a function.
     *
     * This method can be used to implement similar functionality as provided by [[invoke()]] in other
     * components.
     *
     * @param callable $callback callable to be invoked.
     * @param array $params The array of parameters for the function, can be either numeric or associative.
     * @return array The resolved dependencies.
     * @throws InvalidConfigException if a dependency cannot be resolved or if a dependency cannot be fulfilled.
     * @throws NotInstantiableException If resolved to an abstract class or an interface (since 2.0.9)
     * @since 2.0.7
     */
    public function resolveCallableDependencies(callable $callback, $params = [])
    {
        // 如果是方法
        if (is_array($callback)) {
            $reflection = new \ReflectionMethod($callback[0], $callback[1]);
        // 如果是函数
        } else {
            $reflection = new \ReflectionFunction($callback);
        }

        $args = [];
        // 判断是否是关联数组
        $associative = ArrayHelper::isAssociative($params);
        // 反射获取参数遍历
        foreach ($reflection->getParameters() as $param) {
            $name = $param->getName();
            // 看是否是类类型
            if (($class = $param->getClass()) !== null) {
                $className = $class->getName();
                //是关联数组 and 是给参数赋值的值 and 继承关系
                if ($associative && isset($params[$name]) && $params[$name] instanceof $className) {
                    $args[] = $params[$name];
                    unset($params[$name]);
                // 如果不是关联数组，则第一参数为对象型的
                } elseif (!$associative && isset($params[0]) && $params[0] instanceof $className) {
                    $args[] = array_shift($params);
                // 容器获取对象
                } elseif (isset(Yii::$app) && Yii::$app->has($name) && ($obj = Yii::$app->get($name)) instanceof $className) {
                    $args[] = $obj;
                } else {
                    // If the argument is optional we catch not instantiable exceptions
                    try {
                        $args[] = $this->get($className);
                    } catch (NotInstantiableException $e) {
                        if ($param->isDefaultValueAvailable()) {
                            $args[] = $param->getDefaultValue();
                        } else {
                            throw $e;
                        }
                    }

                }
            } elseif ($associative && isset($params[$name])) {
                $args[] = $params[$name];
                unset($params[$name]);
            // 不是关联数组 and 长度大一0
            } elseif (!$associative && count($params)) {
                $args[] = array_shift($params);
            // 有默认值
            } elseif ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
            // 检查是否可选
            } elseif (!$param->isOptional()) {
                $funcName = $reflection->getName();
                throw new InvalidConfigException("Missing required parameter \"$name\" when calling \"$funcName\".");
            }
        }

        foreach ($params as $value) {
            $args[] = $value;
        }
        return $args;
    }

    /**
     * Registers class definitions within this container.
     *
     * @param array $definitions array of definitions. There are two allowed formats of array.
     * The first format:
     *  - key: class name, interface name or alias name. The key will be passed to the [[set()]] method
     *    as a first argument `$class`.
     *  - value: the definition associated with `$class`. Possible values are described in
     *    [[set()]] documentation for the `$definition` parameter. Will be passed to the [[set()]] method
     *    as the second argument `$definition`.
     *
     * Example:
     * ```php
     * $container->setDefinitions([
     *     'yii\web\Request' => 'app\components\Request',
     *     'yii\web\Response' => [
     *         'class' => 'app\components\Response',
     *         'format' => 'json'
     *     ],
     *     'foo\Bar' => function () {
     *         $qux = new Qux;
     *         $foo = new Foo($qux);
     *         return new Bar($foo);
     *     }
     * ]);
     * ```
     *
     * The second format:
     *  - key: class name, interface name or alias name. The key will be passed to the [[set()]] method
     *    as a first argument `$class`.
     *  - value: array of two elements. The first element will be passed the [[set()]] method as the
     *    second argument `$definition`, the second one — as `$params`.
     *
     * Example:
     * ```php
     * $container->setDefinitions([
     *     'foo\Bar' => [
     *          ['class' => 'app\Bar'],
     *          [Instance::of('baz')]
     *      ]
     * ]);
     * ```
     *
     * @see set() to know more about possible values of definitions
     * @since 2.0.11
     */
    public function setDefinitions(array $definitions)
    {
        foreach ($definitions as $class => $definition) {
            if (count($definition) === 2 && array_values($definition) === $definition) {
                $this->set($class, $definition[0], $definition[1]);
                continue;
            }

            $this->set($class, $definition);
        }
    }

    /**
     * Registers class definitions as singletons within this container by calling [[setSingleton()]]
     *
     * @param array $singletons array of singleton definitions. See [[setDefinitions()]]
     * for allowed formats of array.
     *
     * @see setDefinitions() for allowed formats of $singletons parameter
     * @see setSingleton() to know more about possible values of definitions
     * @since 2.0.11
     */
    public function setSingletons(array $singletons)
    {
        foreach ($singletons as $class => $definition) {
            if (count($definition) === 2 && array_values($definition) === $definition) {
                $this->setSingleton($class, $definition[0], $definition[1]);
                continue;
            }

            $this->setSingleton($class, $definition);
        }
    }
}
