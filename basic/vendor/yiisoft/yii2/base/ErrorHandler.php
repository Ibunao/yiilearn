<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\base;

use Yii;
use yii\helpers\VarDumper;
use yii\web\HttpException;

/**
 * ErrorHandler 处理未捕捉的错误或异常
 * ErrorHandler handles uncaught PHP errors and exceptions.
 *
 * ErrorHandler is configured as an application component in [[\yii\base\Application]] by default.
 * You can access that instance via `Yii::$app->errorHandler`.
 *
 * For more details and usage information on ErrorHandler, see the [guide article on handling errors](guide:runtime-handling-errors).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @author Alexander Makarov <sam@rmcreative.ru>
 * @author Carsten Brandt <mail@cebe.cc>
 * @since 2.0
 */
abstract class ErrorHandler extends Component
{
    /**
     * 是否在错误显示之前丢弃现有的页面输出
     * @var bool whether to discard any existing page output before error display. Defaults to true.
     */
    public $discardExistingOutput = true;
    /**
     * 保留内存的大小。一部分内存是预先分配的。
    *当出现内存不足问题时，错误处理程序能够处理错误。
    *这种保留记忆的帮助。如果将此值设置为0，则不会保留内存。
    *默认为256 kb。
     * @var int the size of the reserved memory. A portion of memory is pre-allocated so that
     * when an out-of-memory issue occurs, the error handler is able to handle the error with
     * the help of this reserved memory. If you set this value to be 0, no memory will be reserved.
     * Defaults to 256KB.
     */
    public $memoryReserveSize = 262144;
    /**
     * @var \Exception|null the exception that is being handled currently.
     */
    public $exception;

    /**
     * 用于为致命错误处理程序保留内存。
     * @var string Used to reserve memory for fatal error handler.
     */
    private $_memoryReserve;
    /**
     * @var \Exception from HHVM error that stores backtrace
     */
    private $_hhvmException;


    /**
     * 注册错误处理机制
     * Register this error handler
     */
    public function register()
    {
        // 关闭php报错显示
        ini_set('display_errors', false);
        // 设置用户自定义的异常处理函数
        // [PHP异常处理函数set_exception_handler()的用法](https://www.cnblogs.com/52php/p/5659969.html)
        set_exception_handler([$this, 'handleException']);
        // 设置用户自定义的错误处理函数
        /**
         * [set_error_handler和set_exception_handler方法介绍](http://blog.csdn.net/zhang197093/article/details/75094816)  
         * 只能捕捉一些非严重错误(致命错误而终止的要放在register_shutdown_function中处理)
         */
        if (defined('HHVM_VERSION')) {
            // HHVM是一个开源的PHP虚拟机，使用JIT的编译方式以及其他技术，让PHP代码的执行性能大幅提升。
            set_error_handler([$this, 'handleHhvmError']);
        } else {
            set_error_handler([$this, 'handleError']);
        }
        if ($this->memoryReserveSize > 0) {
            $this->_memoryReserve = str_repeat('x', $this->memoryReserveSize);
        }
        // 注册一个会在php中止时执行的函数
        /**
         * [函数详解](http://blog.csdn.net/jiandanokok/article/details/75193688)
         * PHP中止的情况有三种：
            执行完成
            exit/die导致的中止
            发生致命错误中止
         */
        register_shutdown_function([$this, 'handleFatalError']);
    }

    /**
     * 释放注册的自定义错误/异常处理
     * Unregisters this error handler by restoring the PHP error and exception handlers.
     */
    public function unregister()
    {
        // 释放自定义错误和异常处理函数
        restore_error_handler();
        restore_exception_handler();
    }

    /**
     * 处理未捕捉到的异常
     * Handles uncaught PHP exceptions.
     *
     * This method is implemented as a PHP exception handler.
     *
     * @param \Exception $exception the exception that is not caught
     */
    public function handleException($exception)
    {
        // 如果是继承 ExitException的异常不管
        if ($exception instanceof ExitException) {
            return;
        }

        $this->exception = $exception;
        // 在处理异常时禁用错误捕获以避免递归错误。
        // disable error capturing to avoid recursive errors while handling exceptions
        $this->unregister();

        // set preventive HTTP status code to 500 in case error handling somehow fails and headers are sent
        // HTTP exceptions will override this value in renderException()
        if (PHP_SAPI !== 'cli') {
            // 设置响应的 HTTP 状态码
            http_response_code(500);
        }

        try {
            // 记录错误日志
            $this->logException($exception);
            // 假设说你要看报错之前变量的信息，将这段代码注释掉
            if ($this->discardExistingOutput) {
                // 清 ob
                $this->clearOutput();
            }
            // 设置相应的异常数据
            $this->renderException($exception);
            // 如果不是 test环境,将错误信息写入到日志
            if (!YII_ENV_TEST) {
                \Yii::getLogger()->flush(true);
                if (defined('HHVM_VERSION')) {
                    flush();
                }
                exit(1);
            }
        // 如果在处理异常的时候又 throw 出异常
        } catch (\Exception $e) {
            // an other exception could be thrown while displaying the exception
            $this->handleFallbackExceptionMessage($e, $exception);
        } catch (\Throwable $e) {
            // additional check for \Throwable introduced in PHP 7
            $this->handleFallbackExceptionMessage($e, $exception);
        }

        $this->exception = null;
    }

    /**
     * 处理错误时发生错误调用
     * Handles exception thrown during exception processing in [[handleException()]].
     * @param \Exception|\Throwable $exception Exception that was thrown during main exception processing.
     * @param \Exception $previousException Main exception processed in [[handleException()]].
     * @since 2.0.11
     */
    protected function handleFallbackExceptionMessage($exception, $previousException) {
        $msg = "An Error occurred while handling another error:\n";
        $msg .= (string) $exception;
        $msg .= "\nPrevious exception:\n";
        $msg .= (string) $previousException;
        if (YII_DEBUG) {
            if (PHP_SAPI === 'cli') {
                echo $msg . "\n";
            } else {
                echo '<pre>' . htmlspecialchars($msg, ENT_QUOTES, Yii::$app->charset) . '</pre>';
            }
        } else {
            echo 'An internal server error occurred.';
        }
        $msg .= "\n\$_SERVER = " . VarDumper::export($_SERVER);
        // 记录到PHP日志
        error_log($msg);
        if (defined('HHVM_VERSION')) {
            flush();
        }
        exit(1);
    }

    /**
     * Handles HHVM execution errors such as warnings and notices.
     *
     * This method is used as a HHVM error handler. It will store exception that will
     * be used in fatal error handler
     *
     * @param int $code the level of the error raised.
     * @param string $message the error message.
     * @param string $file the filename that the error was raised in.
     * @param int $line the line number the error was raised at.
     * @param mixed $context
     * @param mixed $backtrace trace of error
     * @return bool whether the normal error handler continues.
     *
     * @throws ErrorException
     * @since 2.0.6
     */
    public function handleHhvmError($code, $message, $file, $line, $context, $backtrace)
    {
        if ($this->handleError($code, $message, $file, $line)) {
            return true;
        }
        if (E_ERROR & $code) {
            $exception = new ErrorException($message, $code, $code, $file, $line);
            $ref = new \ReflectionProperty('\Exception', 'trace');
            $ref->setAccessible(true);
            $ref->setValue($exception, $backtrace);
            $this->_hhvmException = $exception;
        }
        return false;
    }

    /**
     * Handles PHP execution errors such as warnings and notices.
     *
     * This method is used as a PHP error handler. It will simply raise an [[ErrorException]].
     *
     * @param int $code the level of the error raised.
     * @param string $message the error message.
     * @param string $file the filename that the error was raised in.
     * @param int $line the line number the error was raised at.
     * @return bool whether the normal error handler continues.
     *
     * @throws ErrorException
     */
    public function handleError($code, $message, $file, $line)
    {
        if (error_reporting() & $code) {
            // load ErrorException manually here because autoloading them will not work
            // when error occurs while autoloading a class
            if (!class_exists('yii\\base\\ErrorException', false)) {
                require_once(__DIR__ . '/ErrorException.php');
            }
            $exception = new ErrorException($message, $code, $code, $file, $line);

            // in case error appeared in __toString method we can't throw any exception
            $trace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS);
            array_shift($trace);
            foreach ($trace as $frame) {
                if ($frame['function'] === '__toString') {
                    $this->handleException($exception);
                    if (defined('HHVM_VERSION')) {
                        flush();
                    }
                    exit(1);
                }
            }

            throw $exception;
        }
        return false;
    }

    /**
     * Handles fatal PHP errors
     */
    public function handleFatalError()
    {
        unset($this->_memoryReserve);

        // load ErrorException manually here because autoloading them will not work
        // when error occurs while autoloading a class
        if (!class_exists('yii\\base\\ErrorException', false)) {
            require_once(__DIR__ . '/ErrorException.php');
        }

        $error = error_get_last();

        if (ErrorException::isFatalError($error)) {
            if (!empty($this->_hhvmException)) {
                $exception = $this->_hhvmException;
            } else {
                $exception = new ErrorException($error['message'], $error['type'], $error['type'], $error['file'], $error['line']);
            }
            $this->exception = $exception;

            $this->logException($exception);

            if ($this->discardExistingOutput) {
                $this->clearOutput();
            }
            $this->renderException($exception);

            // need to explicitly flush logs because exit() next will terminate the app immediately
            Yii::getLogger()->flush(true);
            if (defined('HHVM_VERSION')) {
                flush();
            }
            exit(1);
        }
    }

    /**
     * Renders the exception.
     * @param \Exception $exception the exception to be rendered.
     */
    abstract protected function renderException($exception);

    /**
     * 记录异常
     * Logs the given exception
     * @param \Exception $exception the exception to be logged
     * @since 2.0.3 this method is now public.
     */
    public function logException($exception)
    {
        $category = get_class($exception);
        if ($exception instanceof HttpException) {
            $category = 'yii\\web\\HttpException:' . $exception->statusCode;
        } elseif ($exception instanceof \ErrorException) {
            // $exception->getSeverity() 获取异常的严重程度
            $category .= ':' . $exception->getSeverity();
        }
        Yii::error($exception, $category);
    }

    /**
     * 清除所有的 ob缓存
     * Removes all output echoed before calling this method.
     */
    public function clearOutput()
    {
        // the following manual level counting is to deal with zlib.output_compression set to On
        for ($level = ob_get_level(); $level > 0; --$level) {
            if (!@ob_end_clean()) {
                ob_clean();
            }
        }
    }

    /**
     * Converts an exception into a PHP error.
     *
     * This method can be used to convert exceptions inside of methods like `__toString()`
     * to PHP errors because exceptions cannot be thrown inside of them.
     * @param \Exception $exception the exception to convert to a PHP error.
     */
    public static function convertExceptionToError($exception)
    {
        trigger_error(static::convertExceptionToString($exception), E_USER_ERROR);
    }

    /**
     * Converts an exception into a simple string.
     * @param \Exception|\Error $exception the exception being converted
     * @return string the string representation of the exception.
     */
    public static function convertExceptionToString($exception)
    {
        if ($exception instanceof Exception && ($exception instanceof UserException || !YII_DEBUG)) {
            $message = "{$exception->getName()}: {$exception->getMessage()}";
        } elseif (YII_DEBUG) {
            if ($exception instanceof Exception) {
                $message = "Exception ({$exception->getName()})";
            } elseif ($exception instanceof ErrorException) {
                $message = "{$exception->getName()}";
            } else {
                $message = 'Exception';
            }
            $message .= " '" . get_class($exception) . "' with message '{$exception->getMessage()}' \n\nin "
                . $exception->getFile() . ':' . $exception->getLine() . "\n\n"
                . "Stack trace:\n" . $exception->getTraceAsString();
        } else {
            $message = 'Error: ' . $exception->getMessage();
        }
        return $message;
    }
}
