<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\web;

use Yii;
use yii\base\Component;
use yii\base\InvalidConfigException;
use yii\base\InvalidParamException;

/**
 * Session provides session data management and the related configurations.
 *
 * Session is a Web application component that can be accessed via `Yii::$app->session`.
 *
 * To start the session, call [[open()]]; To complete and send out session data, call [[close()]];
 * To destroy the session, call [[destroy()]].
 *
 * Session can be used like an array to set and get session data. For example,
 *
 * ```php
 * $session = new Session;
 * $session->open();
 * $value1 = $session['name1'];  // get session variable 'name1'
 * $value2 = $session['name2'];  // get session variable 'name2'
 * foreach ($session as $name => $value) // traverse all session variables
 * $session['name3'] = $value3;  // set session variable 'name3'
 * ```
 *
 * Session can be extended to support customized session storage.
 * To do so, override [[useCustomStorage]] so that it returns true, and
 * override these methods with the actual logic about using custom storage:
 * [[openSession()]], [[closeSession()]], [[readSession()]], [[writeSession()]],
 * [[destroySession()]] and [[gcSession()]].
 *
 * Session also supports a special type of session data, called *flash messages*.
 * A flash message is available only in the current request and the next request.
 * After that, it will be deleted automatically. Flash messages are particularly
 * useful for displaying confirmation messages. To use flash messages, simply
 * call methods such as [[setFlash()]], [[getFlash()]].
 *
 * For more details and usage information on Session, see the [guide article on sessions](guide:runtime-sessions-cookies).
 *
 * @property array $allFlashes Flash messages (key => message or key => [message1, message2]). This property
 * is read-only.
 * @property array $cookieParams The session cookie parameters. This property is read-only.
 * @property int $count The number of session variables. This property is read-only.
 * @property string $flash The key identifying the flash message. Note that flash messages and normal session
 * variables share the same name space. If you have a normal session variable using the same name, its value will
 * be overwritten by this method. This property is write-only.
 * @property float $gCProbability The probability (percentage) that the GC (garbage collection) process is
 * started on every session initialization, defaults to 1 meaning 1% chance.
 * @property bool $hasSessionId Whether the current request has sent the session ID.
 * @property string $id The current session ID.
 * @property bool $isActive Whether the session has started. This property is read-only.
 * @property SessionIterator $iterator An iterator for traversing the session variables. This property is
 * read-only.
 * @property string $name The current session name.
 * @property string $savePath The current session save path, defaults to '/tmp'.
 * @property int $timeout The number of seconds after which data will be seen as 'garbage' and cleaned up. The
 * default value is 1440 seconds (or the value of "session.gc_maxlifetime" set in php.ini).
 * @property bool|null $useCookies The value indicating whether cookies should be used to store session IDs.
 * @property bool $useCustomStorage Whether to use custom storage. This property is read-only.
 * @property bool $useTransparentSessionID Whether transparent sid support is enabled or not, defaults to
 * false.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Session extends Component implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var string the name of the session variable that stores the flash message data.
     */
    public $flashParam = '__flash';
    /**
     * @var \SessionHandlerInterface|array an object implementing the SessionHandlerInterface or a configuration array. If set, will be used to provide persistency instead of build-in methods.
     */
    public $handler;

    /**
     * @var array parameter-value pairs to override default session cookie parameters that are used for session_set_cookie_params() function
     * Array may have the following possible keys: 'lifetime', 'path', 'domain', 'secure', 'httponly'
     * @see http://www.php.net/manual/en/function.session-set-cookie-params.php
     */
    private $_cookieParams = ['httponly' => true];


    /**
     * Initializes the application component.
     * This method is required by IApplicationComponent and is invoked by application.
     */
    public function init()
    {
        parent::init();
        // 注册一个会在php中止时执行的函数
        register_shutdown_function([$this, 'close']);
        // 如果已经开启
        if ($this->getIsActive()) {
            Yii::warning('Session is already started', __METHOD__);
            $this->updateFlashCounters();
        }
    }

    /**
     * Returns a value indicating whether to use custom session storage.
     * This method should be overridden to return true by child classes that implement custom session storage.
     * To implement custom session storage, override these methods: [[openSession()]], [[closeSession()]],
     * [[readSession()]], [[writeSession()]], [[destroySession()]] and [[gcSession()]].
     * @return bool whether to use custom storage.
     */
    public function getUseCustomStorage()
    {
        return false;
    }

    /**
     * 开启session
     * Starts the session.
     */
    public function open()
    {
        // 如果已经开启
        if ($this->getIsActive()) {
            return;
        }
        // 注册session的处理，方便存储到其他介质
        $this->registerSessionHandler();
        // 这一步是为了设置cookie存储sessionid的参数
        $this->setCookieParamsInternal();

        @session_start();

        if ($this->getIsActive()) {
            Yii::info('Session started', __METHOD__);
            $this->updateFlashCounters();
        } else {
            // 获取最后的错误
            $error = error_get_last();
            $message = isset($error['message']) ? $error['message'] : 'Failed to start session.';
            Yii::error($message, __METHOD__);
        }
    }

    /**
     * 注册sessionhandle
     * 接管所有的session管理工作,在DbSession和CacheSession等使用其他存储的会注册自己处理session的机制   
     * Registers session handler.
     * @throws \yii\base\InvalidConfigException
     */
    protected function registerSessionHandler()
    {
        if ($this->handler !== null) {
            if (!is_object($this->handler)) {
                $this->handler = Yii::createObject($this->handler);
            }
            if (!$this->handler instanceof \SessionHandlerInterface) {
                throw new InvalidConfigException('"' . get_class($this) . '::handler" must implement the SessionHandlerInterface.');
            }
            // 设置用户自定义会话存储函数
            YII_DEBUG ? session_set_save_handler($this->handler, false) : @session_set_save_handler($this->handler, false);
        } elseif ($this->getUseCustomStorage()) {
            if (YII_DEBUG) {
                session_set_save_handler(
                    [$this, 'openSession'],
                    [$this, 'closeSession'],
                    [$this, 'readSession'],
                    [$this, 'writeSession'],
                    [$this, 'destroySession'],
                    [$this, 'gcSession']
                );
            } else {
                @session_set_save_handler(
                    [$this, 'openSession'],
                    [$this, 'closeSession'],
                    [$this, 'readSession'],
                    [$this, 'writeSession'],
                    [$this, 'destroySession'],
                    [$this, 'gcSession']
                );
            }
        }
    }

    /**
     * 关闭session
     * Ends the current session and store session data.
     */
    public function close()
    {
        if ($this->getIsActive()) {
            YII_DEBUG ? session_write_close() : @session_write_close();
        }
    }

    /**
     * 释放所有会话变量，并销毁注册到会话中的所有数据。
     * Frees all session variables and destroys all data registered to a session.
     *
     * This method has no effect when session is not [[getIsActive()|active]].
     * Make sure to call [[open()]] before calling it.
     * @see open()
     * @see isActive
     */
    public function destroy()
    {
        if ($this->getIsActive()) {
            // 关闭又开启应该是为了终止没有写完的session吧
            $sessionId = session_id();
            $this->close();
            $this->setId($sessionId);
            $this->open();
            session_unset();
            session_destroy();
            $this->setId($sessionId);
        }
    }

    /**
     * 是否已经开启session
     * @return bool whether the session has started
     */
    public function getIsActive()
    {
        // 返回当前会话状态
        return session_status() === PHP_SESSION_ACTIVE;
    }

    private $_hasSessionId;

    /**
     * 返回一个值，指示当前的请求是否发送了会话ID
     * Returns a value indicating whether the current request has sent the session ID.
     * The default implementation will check cookie and $_GET using the session name.
     * If you send session ID via other ways, you may need to override this method
     * or call [[setHasSessionId()]] to explicitly set whether the session ID is sent.
     * @return bool whether the current request has sent the session ID.
     */
    public function getHasSessionId()
    {
        if ($this->_hasSessionId === null) {
            $name = $this->getName();
            $request = Yii::$app->getRequest();
            // ini_get — 获取一个配置选项的值
            if (!empty($_COOKIE[$name]) && ini_get('session.use_cookies')) {
                $this->_hasSessionId = true;
            } elseif (!ini_get('session.use_only_cookies') && ini_get('session.use_trans_sid')) {
                $this->_hasSessionId = $request->get($name) != '';
            } else {
                $this->_hasSessionId = false;
            }
        }

        return $this->_hasSessionId;
    }

    /**
     * Sets the value indicating whether the current request has sent the session ID.
     * This method is provided so that you can override the default way of determining
     * whether the session ID is sent.
     * @param bool $value whether the current request has sent the session ID.
     */
    public function setHasSessionId($value)
    {
        $this->_hasSessionId = $value;
    }

    /**
     * 获取sessionid
     * Gets the session ID.
     * This is a wrapper for [PHP session_id()](http://php.net/manual/en/function.session-id.php).
     * @return string the current session ID
     */
    public function getId()
    {
        return session_id();
    }

    /**
     * 设置sessionid
     * Sets the session ID.
     * This is a wrapper for [PHP session_id()](http://php.net/manual/en/function.session-id.php).
     * @param string $value the session ID for the current session
     */
    public function setId($value)
    {
        session_id($value);
    }

    /**
     * 使用新生成的一个更新当前会话ID,并将原来的session复制一份
     * Updates the current session ID with a newly generated one.
     *
     * Please refer to <http://php.net/session_regenerate_id> for more details.
     *
     * This method has no effect when session is not [[getIsActive()|active]].
     * Make sure to call [[open()]] before calling it.
     *
     * @param bool $deleteOldSession Whether to delete the old associated session file or not.
     * @see open()
     * @see isActive
     */
    public function regenerateID($deleteOldSession = false)
    {
        if ($this->getIsActive()) {
            // add @ to inhibit possible warning due to race condition
            // https://github.com/yiisoft/yii2/pull/1812
            if (YII_DEBUG && !headers_sent()) {
                session_regenerate_id($deleteOldSession);
            } else {
                @session_regenerate_id($deleteOldSession);
            }
        }
    }

    /**
     * 当前的session名
     * Gets the name of the current session.
     * This is a wrapper for [PHP session_name()](http://php.net/manual/en/function.session-name.php).
     * @return string the current session name
     */
    public function getName()
    {
        return session_name();
    }

    /**
     * Sets the name for the current session.
     * This is a wrapper for [PHP session_name()](http://php.net/manual/en/function.session-name.php).
     * @param string $value the session name for the current session, must be an alphanumeric string.
     * It defaults to "PHPSESSID".
     */
    public function setName($value)
    {
        session_name($value);
    }

    /**
     * 获取session保存路径
     * Gets the current session save path.
     * This is a wrapper for [PHP session_save_path()](http://php.net/manual/en/function.session-save-path.php).
     * @return string the current session save path, defaults to '/tmp'.
     */
    public function getSavePath()
    {
        return session_save_path();
    }

    /**
     * 设置session保存路径
     * Sets the current session save path.
     * This is a wrapper for [PHP session_save_path()](http://php.net/manual/en/function.session-save-path.php).
     * @param string $value the current session save path. This can be either a directory name or a [path alias](guide:concept-aliases).
     * @throws InvalidParamException if the path is not a valid directory
     */
    public function setSavePath($value)
    {
        $path = Yii::getAlias($value);
        if (is_dir($path)) {
            session_save_path($path);
        } else {
            throw new InvalidParamException("Session save path is not a valid directory: $value");
        }
    }

    /**
     * @return array the session cookie parameters.
     * @see http://php.net/manual/en/function.session-get-cookie-params.php
     */
    public function getCookieParams()
    {
        // session_get_cookie_params 获取会话 cookie 参数 
        // array_change_key_case 将数组中的所有键名修改为全大写或小写
        return array_merge(session_get_cookie_params(), array_change_key_case($this->_cookieParams));
    }

    /**
     * Sets the session cookie parameters.
     * The cookie parameters passed to this method will be merged with the result
     * of `session_get_cookie_params()`.
     * @param array $value cookie parameters, valid keys include: `lifetime`, `path`, `domain`, `secure` and `httponly`.
     * @throws InvalidParamException if the parameters are incomplete.
     * @see http://us2.php.net/manual/en/function.session-set-cookie-params.php
     */
    public function setCookieParams(array $value)
    {
        $this->_cookieParams = $value;
    }

    /**
     * Sets the session cookie parameters.
     * This method is called by [[open()]] when it is about to open the session.
     * @throws InvalidParamException if the parameters are incomplete.
     * @see http://us2.php.net/manual/en/function.session-set-cookie-params.php
     */
    private function setCookieParamsInternal()
    {
        $data = $this->getCookieParams();
        if (isset($data['lifetime'], $data['path'], $data['domain'], $data['secure'], $data['httponly'])) {
            // 设置会话 cookie 参数
            session_set_cookie_params($data['lifetime'], $data['path'], $data['domain'], $data['secure'], $data['httponly']);
        } else {
            throw new InvalidParamException('Please make sure cookieParams contains these elements: lifetime, path, domain, secure and httponly.');
        }
    }

    /**
     * 返回是否用cookie存储的sessionid
     * Returns the value indicating whether cookies should be used to store session IDs.
     * @return bool|null the value indicating whether cookies should be used to store session IDs.
     * @see setUseCookies()
     */
    public function getUseCookies()
    {
        // 是否使用cookie存储
        if (ini_get('session.use_cookies') === '0') {
            return false;
        } elseif (ini_get('session.use_only_cookies') === '1') {
            return true;
        } else {
            return null;
        }
    }

    /**
     * Sets the value indicating whether cookies should be used to store session IDs.
     * Three states are possible:
     *
     * - true: cookies and only cookies will be used to store session IDs.
     * - false: cookies will not be used to store session IDs.
     * - null: if possible, cookies will be used to store session IDs; if not, other mechanisms will be used (e.g. GET parameter)
     *
     * @param bool|null $value the value indicating whether cookies should be used to store session IDs.
     */
    public function setUseCookies($value)
    {
        // 不使用cookie存储
        if ($value === false) {
            ini_set('session.use_cookies', '0');
            ini_set('session.use_only_cookies', '0');
        // 只使用cookie存储
        } elseif ($value === true) {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '1');
        // 如果能使用cookie存储就使用cookie，如果不能则用其他的存储方式
        } else {
            ini_set('session.use_cookies', '1');
            ini_set('session.use_only_cookies', '0');
        }
    }

    /**
     * @return float the probability (percentage) that the GC (garbage collection) process is started on every session initialization, defaults to 1 meaning 1% chance.
     */
    public function getGCProbability()
    {
        return (float) (ini_get('session.gc_probability') / ini_get('session.gc_divisor') * 100);
    }

    /**
     * 设置回收机制参数
     * @param float $value the probability (percentage) that the GC (garbage collection) process is started on every session initialization.
     * @throws InvalidParamException if the value is not between 0 and 100.
     */
    public function setGCProbability($value)
    {
        if ($value >= 0 && $value <= 100) {
            // percent * 21474837 / 2147483647 ≈ percent * 0.01
            ini_set('session.gc_probability', floor($value * 21474836.47));
            ini_set('session.gc_divisor', 2147483647);
        } else {
            throw new InvalidParamException('GCProbability must be a value between 0 and 100.');
        }
    }

    /**
     * @return bool whether transparent sid support is enabled or not, defaults to false.
     */
    public function getUseTransparentSessionID()
    {
        return ini_get('session.use_trans_sid') == 1;
    }

    /**
     * 不知道用来干嘛的
     * @param bool $value whether transparent sid support is enabled or not.
     */
    public function setUseTransparentSessionID($value)
    {
        ini_set('session.use_trans_sid', $value ? '1' : '0');
    }

    /**
     * 获取清理的时间
     * @return int the number of seconds after which data will be seen as 'garbage' and cleaned up.
     * The default value is 1440 seconds (or the value of "session.gc_maxlifetime" set in php.ini).
     */
    public function getTimeout()
    {
        return (int) ini_get('session.gc_maxlifetime');
    }

    /**
     * 设置清理的时间
     * @param int $value the number of seconds after which data will be seen as 'garbage' and cleaned up
     */
    public function setTimeout($value)
    {
        ini_set('session.gc_maxlifetime', $value);
    }

    /**
     * Session open handler.
     * This method should be overridden if [[useCustomStorage]] returns true.
     * @internal Do not call this method directly.
     * @param string $savePath session save path
     * @param string $sessionName session name
     * @return bool whether session is opened successfully
     */
    public function openSession($savePath, $sessionName)
    {
        return true;
    }

    /**
     * Session close handler.
     * This method should be overridden if [[useCustomStorage]] returns true.
     * @internal Do not call this method directly.
     * @return bool whether session is closed successfully
     */
    public function closeSession()
    {
        return true;
    }

    /**
     * Session read handler.
     * This method should be overridden if [[useCustomStorage]] returns true.
     * @internal Do not call this method directly.
     * @param string $id session ID
     * @return string the session data
     */
    public function readSession($id)
    {
        return '';
    }

    /**
     * Session write handler.
     * This method should be overridden if [[useCustomStorage]] returns true.
     * @internal Do not call this method directly.
     * @param string $id session ID
     * @param string $data session data
     * @return bool whether session write is successful
     */
    public function writeSession($id, $data)
    {
        return true;
    }

    /**
     * Session destroy handler.
     * This method should be overridden if [[useCustomStorage]] returns true.
     * @internal Do not call this method directly.
     * @param string $id session ID
     * @return bool whether session is destroyed successfully
     */
    public function destroySession($id)
    {
        return true;
    }

    /**
     * Session GC (garbage collection) handler.
     * This method should be overridden if [[useCustomStorage]] returns true.
     * @internal Do not call this method directly.
     * @param int $maxLifetime the number of seconds after which data will be seen as 'garbage' and cleaned up.
     * @return bool whether session is GCed successfully
     */
    public function gcSession($maxLifetime)
    {
        return true;
    }

    /**
     * Returns an iterator for traversing the session variables.
     * This method is required by the interface [[\IteratorAggregate]].
     * @return SessionIterator an iterator for traversing the session variables.
     */
    public function getIterator()
    {
        $this->open();
        return new SessionIterator;
    }

    /**
     * Returns the number of items in the session.
     * @return int the number of session variables
     */
    public function getCount()
    {
        $this->open();
        return count($_SESSION);
    }

    /**
     * Returns the number of items in the session.
     * This method is required by [[\Countable]] interface.
     * @return int number of items in the session.
     */
    public function count()
    {
        return $this->getCount();
    }

    /**
     * 通过key获取session值
     * Returns the session variable value with the session variable name.
     * If the session variable does not exist, the `$defaultValue` will be returned.
     * @param string $key the session variable name
     * @param mixed $defaultValue the default value to be returned when the session variable does not exist.
     * @return mixed the session variable value, or $defaultValue if the session variable does not exist.
     */
    public function get($key, $defaultValue = null)
    {
        $this->open();
        return isset($_SESSION[$key]) ? $_SESSION[$key] : $defaultValue;
    }

    /**
     * Adds a session variable.
     * If the specified name already exists, the old value will be overwritten.
     * @param string $key session variable name
     * @param mixed $value session variable value
     */
    public function set($key, $value)
    {
        $this->open();
        $_SESSION[$key] = $value;
    }

    /**
     * Removes a session variable.
     * @param string $key the name of the session variable to be removed
     * @return mixed the removed value, null if no such session variable.
     */
    public function remove($key)
    {
        $this->open();
        if (isset($_SESSION[$key])) {
            $value = $_SESSION[$key];
            unset($_SESSION[$key]);

            return $value;
        } else {
            return null;
        }
    }

    /**
     * Removes all session variables
     */
    public function removeAll()
    {
        $this->open();
        foreach (array_keys($_SESSION) as $key) {
            unset($_SESSION[$key]);
        }
    }

    /**
     * @param mixed $key session variable name
     * @return bool whether there is the named session variable
     */
    public function has($key)
    {
        $this->open();
        return isset($_SESSION[$key]);
    }

    /**
     * 跟新Flash次数，这个方法在请求结束的时候会被User中调用session时调用，会把使用过的flash信息给清除掉  
     * Updates the counters for flash messages and removes outdated flash messages.
     * This method should only be called once in [[init()]].
     */
    protected function updateFlashCounters()
    {
        // var_dump($_SESSION);exit;
        $counters = $this->get($this->flashParam, []);
        if (is_array($counters)) {
            foreach ($counters as $key => $count) {
                if ($count > 0) {
                    unset($counters[$key], $_SESSION[$key]);
                } elseif ($count == 0) {
                    $counters[$key]++;
                }
            }
            $_SESSION[$this->flashParam] = $counters;
        } else {
            // fix the unexpected problem that flashParam doesn't return an array
            unset($_SESSION[$this->flashParam]);
        }
    }

    /**
     * Returns a flash message.
     * @param string $key the key identifying the flash message
     * @param mixed $defaultValue value to be returned if the flash message does not exist.
     * @param bool $delete whether to delete this flash message right after this method is called.
     * If false, the flash message will be automatically deleted in the next request.
     * @return mixed the flash message or an array of messages if addFlash was used
     * @see setFlash()
     * @see addFlash()
     * @see hasFlash()
     * @see getAllFlashes()
     * @see removeFlash()
     */
    public function getFlash($key, $defaultValue = null, $delete = false)
    {
        $counters = $this->get($this->flashParam, []);
        if (isset($counters[$key])) {
            $value = $this->get($key, $defaultValue);
            if ($delete) {
                $this->removeFlash($key);
            } elseif ($counters[$key] < 0) {
                // mark for deletion in the next request
                $counters[$key] = 1;
                $_SESSION[$this->flashParam] = $counters;
            }

            return $value;
        } else {
            return $defaultValue;
        }
    }

    /**
     * Returns all flash messages.
     *
     * You may use this method to display all the flash messages in a view file:
     *
     * ```php
     * <?php
     * foreach (Yii::$app->session->getAllFlashes() as $key => $message) {
     *     echo '<div class="alert alert-' . $key . '">' . $message . '</div>';
     * } ?>
     * ```
     *
     * With the above code you can use the [bootstrap alert][] classes such as `success`, `info`, `danger`
     * as the flash message key to influence the color of the div.
     *
     * Note that if you use [[addFlash()]], `$message` will be an array, and you will have to adjust the above code.
     *
     * [bootstrap alert]: http://getbootstrap.com/components/#alerts
     *
     * @param bool $delete whether to delete the flash messages right after this method is called.
     * If false, the flash messages will be automatically deleted in the next request.
     * @return array flash messages (key => message or key => [message1, message2]).
     * @see setFlash()
     * @see addFlash()
     * @see getFlash()
     * @see hasFlash()
     * @see removeFlash()
     */
    public function getAllFlashes($delete = false)
    {
        $counters = $this->get($this->flashParam, []);
        $flashes = [];
        foreach (array_keys($counters) as $key) {
            if (array_key_exists($key, $_SESSION)) {
                $flashes[$key] = $_SESSION[$key];
                if ($delete) {
                    unset($counters[$key], $_SESSION[$key]);
                } elseif ($counters[$key] < 0) {
                    // mark for deletion in the next request
                    $counters[$key] = 1;
                }
            } else {
                unset($counters[$key]);
            }
        }

        $_SESSION[$this->flashParam] = $counters;

        return $flashes;
    }

    /**
     * Sets a flash message.
     * A flash message will be automatically deleted after it is accessed in a request and the deletion will happen
     * in the next request.
     * If there is already an existing flash message with the same key, it will be overwritten by the new one.
     * @param string $key the key identifying the flash message. Note that flash messages
     * and normal session variables share the same name space. If you have a normal
     * session variable using the same name, its value will be overwritten by this method.
     * @param mixed $value flash message
     * @param bool $removeAfterAccess whether the flash message should be automatically removed only if
     * it is accessed. If false, the flash message will be automatically removed after the next request,
     * regardless if it is accessed or not. If true (default value), the flash message will remain until after
     * it is accessed.
     * @see getFlash()
     * @see addFlash()
     * @see removeFlash()
     */
    public function setFlash($key, $value = true, $removeAfterAccess = true)
    {
        $counters = $this->get($this->flashParam, []);
        // 默认为调用一次后删除
        $counters[$key] = $removeAfterAccess ? -1 : 0;
        $_SESSION[$key] = $value;
        $_SESSION[$this->flashParam] = $counters;
    }

    /**
     * Adds a flash message.
     * If there are existing flash messages with the same key, the new one will be appended to the existing message array.
     * @param string $key the key identifying the flash message.
     * @param mixed $value flash message
     * @param bool $removeAfterAccess whether the flash message should be automatically removed only if
     * it is accessed. If false, the flash message will be automatically removed after the next request,
     * regardless if it is accessed or not. If true (default value), the flash message will remain until after
     * it is accessed.
     * @see getFlash()
     * @see setFlash()
     * @see removeFlash()
     */
    public function addFlash($key, $value = true, $removeAfterAccess = true)
    {
        $counters = $this->get($this->flashParam, []);
        $counters[$key] = $removeAfterAccess ? -1 : 0;
        $_SESSION[$this->flashParam] = $counters;
        if (empty($_SESSION[$key])) {
            $_SESSION[$key] = [$value];
        } else {
            if (is_array($_SESSION[$key])) {
                $_SESSION[$key][] = $value;
            } else {
                $_SESSION[$key] = [$_SESSION[$key], $value];
            }
        }
    }

    /**
     * Removes a flash message.
     * @param string $key the key identifying the flash message. Note that flash messages
     * and normal session variables share the same name space.  If you have a normal
     * session variable using the same name, it will be removed by this method.
     * @return mixed the removed flash message. Null if the flash message does not exist.
     * @see getFlash()
     * @see setFlash()
     * @see addFlash()
     * @see removeAllFlashes()
     */
    public function removeFlash($key)
    {
        $counters = $this->get($this->flashParam, []);
        $value = isset($_SESSION[$key], $counters[$key]) ? $_SESSION[$key] : null;
        unset($counters[$key], $_SESSION[$key]);
        $_SESSION[$this->flashParam] = $counters;

        return $value;
    }

    /**
     * Removes all flash messages.
     * Note that flash messages and normal session variables share the same name space.
     * If you have a normal session variable using the same name, it will be removed
     * by this method.
     * @see getFlash()
     * @see setFlash()
     * @see addFlash()
     * @see removeFlash()
     */
    public function removeAllFlashes()
    {
        $counters = $this->get($this->flashParam, []);
        foreach (array_keys($counters) as $key) {
            unset($_SESSION[$key]);
        }
        unset($_SESSION[$this->flashParam]);
    }

    /**
     * Returns a value indicating whether there are flash messages associated with the specified key.
     * @param string $key key identifying the flash message type
     * @return bool whether any flash messages exist under specified key
     */
    public function hasFlash($key)
    {
        return $this->getFlash($key) !== null;
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     * @param mixed $offset the offset to check on
     * @return bool
     */
    public function offsetExists($offset)
    {
        $this->open();

        return isset($_SESSION[$offset]);
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     * @param int $offset the offset to retrieve element.
     * @return mixed the element at the offset, null if no element is found at the offset
     */
    public function offsetGet($offset)
    {
        $this->open();

        return isset($_SESSION[$offset]) ? $_SESSION[$offset] : null;
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     * @param int $offset the offset to set element
     * @param mixed $item the element value
     */
    public function offsetSet($offset, $item)
    {
        $this->open();
        $_SESSION[$offset] = $item;
    }

    /**
     * This method is required by the interface [[\ArrayAccess]].
     * @param mixed $offset the offset to unset element
     */
    public function offsetUnset($offset)
    {
        $this->open();
        unset($_SESSION[$offset]);
    }
}
