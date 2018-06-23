<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;

/**
 * Application is the base class for all application classes.
 *
 * For more details and usage information on Application, see the [guide article on applications](guide:structure-applications).
 *
 * @property \yii\web\AssetManager $assetManager The asset manager application component. This property is
 * read-only.
 * @property \yii\rbac\ManagerInterface $authManager The auth manager application component. Null is returned
 * if auth manager is not configured. This property is read-only.
 * @property string $basePath The root directory of the application.
 * @property \yii\caching\Cache $cache The cache application component. Null if the component is not enabled.
 * This property is read-only.
 * @property array $container Values given in terms of name-value pairs. This property is write-only.
 * @property \yii\db\Connection $db The database connection. This property is read-only.
 * @property \yii\web\ErrorHandler|\yii\console\ErrorHandler $errorHandler The error handler application
 * component. This property is read-only.
 * @property \yii\i18n\Formatter $formatter The formatter application component. This property is read-only.
 * @property \yii\i18n\I18N $i18n The internationalization application component. This property is read-only.
 * @property \yii\log\Dispatcher $log The log dispatcher application component. This property is read-only.
 * @property \yii\mail\MailerInterface $mailer The mailer application component. This property is read-only.
 * @property \yii\web\Request|\yii\console\Request $request The request component. This property is read-only.
 * @property \yii\web\Response|\yii\console\Response $response The response component. This property is
 * read-only.
 * @property string $runtimePath The directory that stores runtime files. Defaults to the "runtime"
 * subdirectory under [[basePath]].
 * @property \yii\base\Security $security The security application component. This property is read-only.
 * @property string $timeZone The time zone used by this application.
 * @property string $uniqueId The unique ID of the module. This property is read-only.
 * @property \yii\web\UrlManager $urlManager The URL manager for this application. This property is read-only.
 * @property string $vendorPath The directory that stores vendor files. Defaults to "vendor" directory under
 * [[basePath]].
 * @property View|\yii\web\View $view The view application component that is used to render various view
 * files. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
abstract class Application extends Module
{
    /**
     * 请求之前事件方法
     * @event Event an event raised before the application starts to handle a request.
     */
    const EVENT_BEFORE_REQUEST = 'beforeRequest';
    /**
     * 请求之后的事件方法
     * @event Event an event raised after the application successfully handles a request (before the response is sent out).
     */
    const EVENT_AFTER_REQUEST = 'afterRequest';
    //设置 application 声明周期中的状态码
    /**
     * Application state used by [[state]]: application just started.
     */
    const STATE_BEGIN = 0;
    /**
     * Application state used by [[state]]: application is initializing.
     */
    const STATE_INIT = 1;
    /**
     * Application state used by [[state]]: application is triggering [[EVENT_BEFORE_REQUEST]].
     */
    const STATE_BEFORE_REQUEST = 2;
    /**
     * Application state used by [[state]]: application is handling the request.
     */
    const STATE_HANDLING_REQUEST = 3;
    /**
     * Application state used by [[state]]: application is triggering [[EVENT_AFTER_REQUEST]]..
     */
    const STATE_AFTER_REQUEST = 4;
    /**
     * Application state used by [[state]]: application is about to send response.
     */
    const STATE_SENDING_RESPONSE = 5;
    /**
     * Application state used by [[state]]: application has ended.
     */
    const STATE_END = 6;

    /**
     * 要加载的 控制器
     * @var string the namespace that controller classes are located in.
     * This namespace will be used to load controller classes by prepending it to the controller class name.
     * The default namespace is `app\controllers`.
     *
     * Please refer to the [guide about class autoloading](guide:concept-autoloading.md) for more details.
     */
    public $controllerNamespace = 'app\\controllers';
    /**
     * @var string the application name.
     */
    public $name = 'My Application';
    /**
     * @var string the charset currently used for the application.
     */
    public $charset = 'UTF-8';
    /**
     * 目标语言
     * @var string the language that is meant to be used for end users. It is recommended that you
     * use [IETF language tags](http://en.wikipedia.org/wiki/IETF_language_tag). For example, `en` stands
     * for English, while `en-US` stands for English (United States).
     * @see sourceLanguage
     */
    public $language = 'en-US';
    /**
     * 源语言
     * @var string the language that the application is written in. This mainly refers to
     * the language that the messages and view files are written in.
     * @see language
     */
    public $sourceLanguage = 'en-US';
    /**
     * 当前请求控制器的实例
     * @var Controller the currently active controller instance
     */
    public $controller;
    /**
     *
     * @var string|bool the layout that should be applied for views in this application. Defaults to 'main'.
     * If this is false, layout will be disabled.
     */
    public $layout = 'main';
    /**
     * 请求route
     * @var string the requested route
     */
    public $requestedRoute;
    /**
     * 请求的action
     * @var Action the requested Action. If null, it means the request cannot be resolved into an action.
     */
    public $requestedAction;
    /**
     * 请求的参数
     * @var array the parameters supplied to the requested action.
     */
    public $requestedParams;
    /**
     * @var array list of installed Yii extensions. Each array element represents a single extension
     * with the following structure:
     *
     * ```php
     * [
     *     'name' => 'extension name',
     *     'version' => 'version number',
     *     'bootstrap' => 'BootstrapClassName',  // optional, may also be a configuration array
     *     'alias' => [
     *         '@alias1' => 'to/path1',
     *         '@alias2' => 'to/path2',
     *     ],
     * ]
     * ```
     *
     * The "bootstrap" class listed above will be instantiated during the application
     * [[bootstrap()|bootstrapping process]]. If the class implements [[BootstrapInterface]],
     * its [[BootstrapInterface::bootstrap()|bootstrap()]] method will be also be called.
     *
     * If not set explicitly in the application config, this property will be populated with the contents of
     * `@vendor/yiisoft/extensions.php`.
     */
    public $extensions;
    /**
     * 
     * @var array list of components that should be run during the application [[bootstrap()|bootstrapping process]].
     *
     * Each component may be specified in one of the following formats:
     *
     * - an application component ID as specified via [[components]].
     * - a module ID as specified via [[modules]].
     * - a class name.
     * - a configuration array.
     *
     * During the bootstrapping process, each component will be instantiated. If the component class
     * implements [[BootstrapInterface]], its [[BootstrapInterface::bootstrap()|bootstrap()]] method
     * will be also be called.
     */
    /**
     * 如果你希望一个 模块 自定义 URL 规则， 你可以将模块ID加入到bootstrap数组中。比如debug和gii模块
     */
    public $bootstrap = [];
    /**
     * 当前application状态
     * @var int the current application state during a request handling life cycle.
     * This property is managed by the application. Do not modify this property.
     */
    public $state;
    /**
     * 存放module实例，以module类名为键
     * @var array list of loaded modules indexed by their class names.
     */
    public $loadedModules = [];


    /**
     * Constructor.
     * @param array $config name-value pairs that will be used to initialize the object properties.
     * Note that the configuration must contain both [[id]] and [[basePath]].
     * @throws InvalidConfigException if either [[id]] or [[basePath]] configuration is missing.
     */
    public function __construct($config = [])
    {
        //赋值$app
        Yii::$app = $this;
        //将对象填充到Yii::$app->loadedModules[]数组
        // Yii::$app->loadedModules['yii\web\Application'] == Yii::$app
        static::setInstance($this);
        //设置$app状态
        $this->state = self::STATE_BEGIN;

        //预初始化，初始化配置信息  加载配置文件的框架信息 如：设置别名，设置框架路径等等 最为重要的是给加载默认组件
        $this->preInit($config);
        //加载配置文件中的异常组件
        $this->registerErrorHandler($config);
        // 将会调用父类Object的构造函数 使用 Yii::configure($this, $config); 进行属性的赋值
        // 跳过了Module的构造方法
        // Yii::$app->components['redis']//输出redis组件实例
        // 将定义的配置文件从的 components 数组中的组件注册到服务定位器 ServiceLocator
        // 将modules数组中的模块注册到Module中的module数组中
        Component::__construct($config);//之后调用init()
    }

    /**
     * 预初始化
     * Pre-initializes the application.
     * This method is called at the beginning of the application constructor.
     * It initializes several important application properties.
     * If you override this method, please make sure you call the parent implementation.
     * @param array $config the application configuration
     * @throws InvalidConfigException if either [[id]] or [[basePath]] configuration is missing.
     */
    public function preInit(&$config)
    {
        // 必须设置id,来标示module的id，$app就是一个父module
        if (!isset($config['id'])) {
            throw new InvalidConfigException('The "id" configuration for the Application is required.');
        }
        // 必须设置项目根路径
        if (isset($config['basePath'])) {
            $this->setBasePath($config['basePath']);
            unset($config['basePath']);
        } else {
            throw new InvalidConfigException('The "basePath" configuration for the Application is required.');
        }
        // 设置vendor目录路径  // set "@vendor"
        if (isset($config['vendorPath'])) {
            $this->setVendorPath($config['vendorPath']);
            unset($config['vendorPath']);
        } else {
            $this->getVendorPath();
        }
        // 设置runtime目录
        if (isset($config['runtimePath'])) {
            $this->setRuntimePath($config['runtimePath']);
            unset($config['runtimePath']);
        } else {
            // 使用默认的
            $this->getRuntimePath();
        }
        // 设置时区
        if (isset($config['timeZone'])) {
            $this->setTimeZone($config['timeZone']);
            unset($config['timeZone']);
        // 如果没有设置时区
        } elseif (!ini_get('date.timezone')) {
            $this->setTimeZone('UTC');
        }
        // 配置容器属性值
        if (isset($config['container'])) {
            $this->setContainer($config['container']);
            unset($config['container']);
        }
        // 合并配置中与核心的模块
        // merge core components with custom components
        foreach ($this->coreComponents() as $id => $component) {
            if (!isset($config['components'][$id])) {
                $config['components'][$id] = $component;
            // 配置核心模块类
            } elseif (is_array($config['components'][$id]) && !isset($config['components'][$id]['class'])) {
                $config['components'][$id]['class'] = $component['class'];
            }
        }
    }

    /**
     * 构造函数中调用
     * @inheritdoc
     */
    public function init()
    {
        $this->state = self::STATE_INIT;
        $this->bootstrap();
    }

    /**
     * 引导方法，加载需要预先加载的模块
     * Initializes extensions and executes bootstrap components.
     * This method is called by [[init()]] after the application has been fully configured.
     * If you override this method, make sure you also call the parent implementation.
     */
    protected function bootstrap()
    {
        // 预先加载extensions文件中的数据
        // 加载配置文件中的别名和bootstrap
        if ($this->extensions === null) {
            $file = Yii::getAlias('@vendor/yiisoft/extensions.php');
            $this->extensions = is_file($file) ? include($file) : [];
        }
        foreach ($this->extensions as $extension) {
            //设置别名
            if (!empty($extension['alias'])) {
                foreach ($extension['alias'] as $name => $path) {
                    Yii::setAlias($name, $path);
                }
            }
            //创建配置中bootstrap的对象
            if (isset($extension['bootstrap'])) {
                $component = Yii::createObject($extension['bootstrap']);
                if ($component instanceof BootstrapInterface) {
                    // 记录日志
                    Yii::trace('Bootstrap with ' . get_class($component) . '::bootstrap()', __METHOD__);
                    $component->bootstrap($this);
                } else {
                    Yii::trace('Bootstrap with ' . get_class($component), __METHOD__);
                }
            }
        }
        // 配置文件设置的 bootstrap 中定义的模块,需要预先加载的
        foreach ($this->bootstrap as $class) {
            $component = null;
            if (is_string($class)) {
                // 服务定位器 ServiceLocator判断是否有，也就是是否已经注册这个组件
                if ($this->has($class)) {
                    // ServiceLocator 获取
                    $component = $this->get($class);
                // 是否存在这个 Module
                } elseif ($this->hasModule($class)) {
                    // 获取模块
                    $component = $this->getModule($class);
                } elseif (strpos($class, '\\') === false) {
                    throw new InvalidConfigException("Unknown bootstrapping component ID: $class");
                }
            }
            // 不是组件components和 Modules 中的数组
            if (!isset($component)) {
                $component = Yii::createObject($class);
            }
            // 如果这个组件继承自 BootstrapInterface 接口，调用启动加载方法
            if ($component instanceof BootstrapInterface) {
                Yii::trace('Bootstrap with ' . get_class($component) . '::bootstrap()', __METHOD__);
                // debug 模块有用，并在里边进行了事件的绑定和路由规则的注册
                // 加载模块前需要预先处理的
                $component->bootstrap($this);
            } else {
                Yii::trace('Bootstrap with ' . get_class($component), __METHOD__);
            }
        }
    }

    /**
     * 注册错误处理程序
     * Registers the errorHandler component as a PHP error handler.
     * @param array $config application config
     */
    protected function registerErrorHandler(&$config)
    {
        // 如果开启yii错误处理机制
        if (YII_ENABLE_ERROR_HANDLER) {
            if (!isset($config['components']['errorHandler']['class'])) {
                echo "Error: no errorHandler component is configured.\n";
                exit(1);
            }
            // 服务定位器 set
            $this->set('errorHandler', $config['components']['errorHandler']);
            unset($config['components']['errorHandler']);
            // 服务定位器get 并调用对象的 register()方法 注册异常、错误处理函数
            $this->getErrorHandler()->register();
        }
    }

    /**
     * Returns an ID that uniquely identifies this module among all modules within the current application.
     * Since this is an application instance, it will always return an empty string.
     * @return string the unique ID of the module.
     */
    public function getUniqueId()
    {
        return '';
    }

    /**
     * 设置根目录
     * Sets the root directory of the application and the @app alias.
     * This method can only be invoked at the beginning of the constructor.
     * @param string $path the root directory of the application.
     * @property string the root directory of the application.
     * @throws InvalidParamException if the directory does not exist.
     */
    public function setBasePath($path)
    {
        parent::setBasePath($path);
        // 设置根目录别名
        Yii::setAlias('@app', $this->getBasePath());
    }

    /**
     * 执行
     * Runs the application.
     * This is the main entrance of an application.
     * @return int the exit status (0 means normal, non-zero values mean abnormal)
     */
    public function run()
    {
        try {
            // 更改状态 相应前
            $this->state = self::STATE_BEFORE_REQUEST;
            // 触发事件
            // 配置中bootstop模块如果有bootstop 或者 extentions 中的bootstop
            // 例如debug 模块的bootstop方法就注册on了事件
            $this->trigger(self::EVENT_BEFORE_REQUEST);

            $this->state = self::STATE_HANDLING_REQUEST;
            // 适应请求request对象处理相应
            $response = $this->handleRequest($this->getRequest());

            // 响应后
            $this->state = self::STATE_AFTER_REQUEST;
            // 触发事件
            $this->trigger(self::EVENT_AFTER_REQUEST);
            //发送
            $this->state = self::STATE_SENDING_RESPONSE;
            $response->send();
            // 结束
            $this->state = self::STATE_END;

            return $response->exitStatus;

        } catch (ExitException $e) {

            $this->end($e->statusCode, isset($response) ? $response : null);
            return $e->statusCode;

        }
    }

    /**
     * 处理请求
     * Handles the specified request.
     *
     * This method should return an instance of [[Response]] or its child class
     * which represents the handling result of the request.
     *
     * @param Request $request the request to be handled
     * @return Response the resulting response
     */
    abstract public function handleRequest($request);

    private $_runtimePath;

    /**
     * 获取runtime 路径
     * Returns the directory that stores runtime files.
     * @return string the directory that stores runtime files.
     * Defaults to the "runtime" subdirectory under [[basePath]].
     */
    public function getRuntimePath()
    {
        if ($this->_runtimePath === null) {
            $this->setRuntimePath($this->getBasePath() . DIRECTORY_SEPARATOR . 'runtime');
        }

        return $this->_runtimePath;
    }

    /**
     * 设置runtime路径
     * Sets the directory that stores runtime files.
     * @param string $path the directory that stores runtime files.
     */
    public function setRuntimePath($path)
    {
        $this->_runtimePath = Yii::getAlias($path);
        Yii::setAlias('@runtime', $this->_runtimePath);
    }

    private $_vendorPath;

    /**
     * 设置默认 vendor 目录
     * Returns the directory that stores vendor files.
     * @return string the directory that stores vendor files.
     * Defaults to "vendor" directory under [[basePath]].
     */
    public function getVendorPath()
    {
        if ($this->_vendorPath === null) {
            $this->setVendorPath($this->getBasePath() . DIRECTORY_SEPARATOR . 'vendor');
        }

        return $this->_vendorPath;
    }

    /**
     * 定义vandor目录
     * Sets the directory that stores vendor files.
     * @param string $path the directory that stores vendor files.
     */
    public function setVendorPath($path)
    {
        $this->_vendorPath = Yii::getAlias($path);
        Yii::setAlias('@vendor', $this->_vendorPath);
        Yii::setAlias('@bower', $this->_vendorPath . DIRECTORY_SEPARATOR . 'bower');
        Yii::setAlias('@npm', $this->_vendorPath . DIRECTORY_SEPARATOR . 'npm');
    }

    /**
     * 返回时区
     * Returns the time zone used by this application.
     * This is a simple wrapper of PHP function date_default_timezone_get().
     * If time zone is not configured in php.ini or application config,
     * it will be set to UTC by default.
     * @return string the time zone used by this application.
     * @see http://php.net/manual/en/function.date-default-timezone-get.php
     */
    public function getTimeZone()
    {
        return date_default_timezone_get();
    }

    /**
     * 设置时区
     * Sets the time zone used by this application.
     * This is a simple wrapper of PHP function date_default_timezone_set().
     * Refer to the [php manual](http://www.php.net/manual/en/timezones.php) for available timezones.
     * @param string $value the time zone used by this application.
     * @see http://php.net/manual/en/function.date-default-timezone-set.php
     */
    public function setTimeZone($value)
    {
        date_default_timezone_set($value);
    }

    /**
     * Returns the database connection component.
     * @return \yii\db\Connection the database connection.
     */
    public function getDb()
    {
        return $this->get('db');
    }

    /**
     * Returns the log dispatcher component.
     * @return \yii\log\Dispatcher the log dispatcher application component.
     */
    public function getLog()
    {
        return $this->get('log');
    }

    /**
     * Returns the error handler component.
     * @return \yii\web\ErrorHandler|\yii\console\ErrorHandler the error handler application component.
     */
    public function getErrorHandler()
    {
        return $this->get('errorHandler');
    }

    /**
     * Returns the cache component.
     * @return \yii\caching\Cache the cache application component. Null if the component is not enabled.
     */
    public function getCache()
    {
        return $this->get('cache', false);
    }

    /**
     * Returns the formatter component.
     * @return \yii\i18n\Formatter the formatter application component.
     */
    public function getFormatter()
    {
        return $this->get('formatter');
    }

    /**
     * Returns the request component.
     * @return \yii\web\Request|\yii\console\Request the request component.
     */
    public function getRequest()
    {
        return $this->get('request');
    }

    /**
     * Returns the response component.
     * @return \yii\web\Response|\yii\console\Response the response component.
     */
    public function getResponse()
    {
        return $this->get('response');
    }

    /**
     * Returns the view object.
     * @return View|\yii\web\View the view application component that is used to render various view files.
     */
    public function getView()
    {
        return $this->get('view');
    }

    /**
     * Returns the URL manager for this application.
     * @return \yii\web\UrlManager the URL manager for this application.
     */
    public function getUrlManager()
    {
        return $this->get('urlManager');
    }

    /**
     * Returns the internationalization (i18n) component
     * @return \yii\i18n\I18N the internationalization application component.
     */
    public function getI18n()
    {
        return $this->get('i18n');
    }

    /**
     * Returns the mailer component.
     * @return \yii\mail\MailerInterface the mailer application component.
     */
    public function getMailer()
    {
        return $this->get('mailer');
    }

    /**
     * Returns the auth manager for this application.
     * @return \yii\rbac\ManagerInterface the auth manager application component.
     * Null is returned if auth manager is not configured.
     */
    public function getAuthManager()
    {
        return $this->get('authManager', false);
    }

    /**
     * 资源管理类
     * Returns the asset manager.
     * @return \yii\web\AssetManager the asset manager application component.
     */
    public function getAssetManager()
    {
        return $this->get('assetManager');
    }

    /**
     * 安全组件
     * Returns the security component.
     * @return \yii\base\Security the security application component.
     */
    public function getSecurity()
    {
        return $this->get('security');
    }

    /**
     * Returns the configuration of core application components.
     * @see set()
     */
    public function coreComponents()
    {
        return [
            'log' => ['class' => 'yii\log\Dispatcher'],
            'view' => ['class' => 'yii\web\View'],
            'formatter' => ['class' => 'yii\i18n\Formatter'],
            'i18n' => ['class' => 'yii\i18n\I18N'],
            'mailer' => ['class' => 'yii\swiftmailer\Mailer'],
            'urlManager' => ['class' => 'yii\web\UrlManager'],
            'assetManager' => ['class' => 'yii\web\AssetManager'],
            'security' => ['class' => 'yii\base\Security'],
        ];
    }

    /**
     * 终止应用程序
     * Terminates the application.
     * This method replaces the `exit()` function by ensuring the application life cycle is completed
     * before terminating the application.
     * @param int $status the exit status (value 0 means normal exit while other values mean abnormal exit).
     * @param Response $response the response to be sent. If not set, the default application [[response]] component will be used.
     * @throws ExitException if the application is in testing mode
     */
    public function end($status = 0, $response = null)
    {
        if ($this->state === self::STATE_BEFORE_REQUEST || $this->state === self::STATE_HANDLING_REQUEST) {
            $this->state = self::STATE_AFTER_REQUEST;
            $this->trigger(self::EVENT_AFTER_REQUEST);
        }

        if ($this->state !== self::STATE_SENDING_RESPONSE && $this->state !== self::STATE_END) {
            $this->state = self::STATE_END;
            $response = $response ? : $this->getResponse();
            $response->send();
        }

        if (YII_ENV_TEST) {
            throw new ExitException($status);
        } else {
            exit($status);
        }
    }

    /**
     * 配置容器
     * Configures [[Yii::$container]] with the $config
     *
     * @param array $config values given in terms of name-value pairs
     * @since 2.0.11
     */
    public function setContainer($config)
    {
        Yii::configure(Yii::$container, $config);
    }
}
