<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\caching;

use Yii;
use yii\helpers\FileHelper;

/**
 * FileCache implements a cache component using files.
 *
 * For each data value being cached, FileCache will store it in a separate file.
 * The cache files are placed under [[cachePath]]. FileCache will perform garbage collection
 * automatically to remove expired cache files.
 *
 * Please refer to [[Cache]] for common cache operations that are supported by FileCache.
 *
 * For more details and usage information on Cache, see the [guide article on caching](guide:caching-overview).
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class FileCache extends Cache
{
    /**
     * 缓存key前缀
     * @var string a string prefixed to every cache key. This is needed when you store
     * cache data under the same [[cachePath]] for different applications to avoid
     * conflict.
     *
     * To ensure interoperability, only alphanumeric characters should be used.
     */
    public $keyPrefix = '';
    /**
     * 缓存的路径
     * @var string the directory to store cache files. You may use [path alias](guide:concept-aliases) here.
     * If not set, it will use the "cache" subdirectory under the application runtime path.
     */
    public $cachePath = '@runtime/cache';
    /**
     * 缓存文件后缀
     * @var string cache file suffix. Defaults to '.bin'.
     */
    public $cacheFileSuffix = '.bin';
    /**
     * @var int the level of sub-directories to store cache files. Defaults to 1.
     * If the system has huge number of cache files (e.g. one million), you may use a bigger value
     * (usually no bigger than 3). Using sub-directories is mainly to ensure the file system
     * is not over burdened with a single directory having too many files.
     */
    public $directoryLevel = 1;
    /**
     * 回收的几率  Defaults to 10, meaning 0.001% chance
     * @var int the probability (parts per million) that garbage collection (GC) should be performed
     * when storing a piece of data in the cache. Defaults to 10, meaning 0.001% chance.
     * This number should be between 0 and 1000000. A value 0 means no GC will be performed at all.
     */
    public $gcProbability = 10;
    /**
     * 文件的权限
     * @var int the permission to be set for newly created cache files.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * If not set, the permission will be determined by the current environment.
     */
    public $fileMode;
    /**
     * 目录的权限
     * @var int the permission to be set for newly created directories.
     * This value will be used by PHP chmod() function. No umask will be applied.
     * Defaults to 0775, meaning the directory is read-writable by owner and group,
     * but read-only for other users.
     */
    public $dirMode = 0775;


    /**
     * Initializes this component by ensuring the existence of the cache path.
     */
    public function init()
    {
        parent::init();
        // 创建文件夹
        $this->cachePath = Yii::getAlias($this->cachePath);
        if (!is_dir($this->cachePath)) {
            FileHelper::createDirectory($this->cachePath, $this->dirMode, true);
        }
    }

    /**
     * Checks whether a specified key exists in the cache.
     * This can be faster than getting the value from the cache if the data is big.
     * Note that this method does not check whether the dependency associated
     * with the cached data, if there is any, has changed. So a call to [[get]]
     * may return false while exists returns true.
     * @param mixed $key a key identifying the cached value. This can be a simple string or
     * a complex data structure consisting of factors representing the key.
     * @return bool true if a value exists in cache, false if the value is not in the cache or expired.
     */
    public function exists($key)
    {
        $cacheFile = $this->getCacheFile($this->buildKey($key));

        return @filemtime($cacheFile) > time();
    }

    /**
     * 通过key获取缓存值
     * Retrieves a value from cache with a specified key.
     * This is the implementation of the method declared in the parent class.
     * @param string $key a unique key identifying the cached value
     * @return string|false the value stored in cache, false if the value is not in the cache or expired.
     */
    protected function getValue($key)
    {
        $cacheFile = $this->getCacheFile($key);

        if (@filemtime($cacheFile) > time()) {
            $fp = @fopen($cacheFile, 'r');
            if ($fp !== false) {
                @flock($fp, LOCK_SH);
                $cacheValue = @stream_get_contents($fp);
                @flock($fp, LOCK_UN);
                @fclose($fp);
                return $cacheValue;
            }
        }

        return false;
    }

    /**
     * 存值
     * Stores a value identified by a key in cache.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached. Other types (If you have disabled [[serializer]]) unable to get is
     * correct in [[getValue()]].
     * @param int $duration the number of seconds in which the cached value will expire. 0 means never expire.
     * @return bool true if the value is successfully stored into cache, false otherwise
     */
    protected function setValue($key, $value, $duration)
    {
        // 垃圾回收
        $this->gc();
        // 获取key对应的缓存文件
        $cacheFile = $this->getCacheFile($key);
        if ($this->directoryLevel > 0) {
            // 创建文件
            @FileHelper::createDirectory(dirname($cacheFile), $this->dirMode, true);
        }
        // 加锁写入
        if (@file_put_contents($cacheFile, $value, LOCK_EX) !== false) {
            if ($this->fileMode !== null) {
                // 设置权限
                @chmod($cacheFile, $this->fileMode);
            }
            // 小于等于0 
            if ($duration <= 0) {
                $duration = 31536000; // 1 year
            }
            // 修改文件的日期
            return @touch($cacheFile, $duration + time());
        } else {
            $error = error_get_last();
            Yii::warning("Unable to write cache file '{$cacheFile}': {$error['message']}", __METHOD__);
            return false;
        }
    }

    /**
     * Stores a value identified by a key into cache if the cache does not contain this key.
     * This is the implementation of the method declared in the parent class.
     *
     * @param string $key the key identifying the value to be cached
     * @param string $value the value to be cached. Other types (if you have disabled [[serializer]]) unable to get is
     * correct in [[getValue()]].
     * @param int $duration the number of seconds in which the cached value will expire. 0 means never expire.
     * @return bool true if the value is successfully stored into cache, false otherwise
     */
    protected function addValue($key, $value, $duration)
    {
        $cacheFile = $this->getCacheFile($key);
        if (@filemtime($cacheFile) > time()) {
            return false;
        }

        return $this->setValue($key, $value, $duration);
    }

    /**
     * Deletes a value with the specified key from cache
     * This is the implementation of the method declared in the parent class.
     * @param string $key the key of the value to be deleted
     * @return bool if no error happens during deletion
     */
    protected function deleteValue($key)
    {
        $cacheFile = $this->getCacheFile($key);

        return @unlink($cacheFile);
    }

    /**
     * 通过key获取缓存文件路径
     * Returns the cache file path given the cache key.
     * @param string $key cache key
     * @return string the cache file path
     */
    protected function getCacheFile($key)
    {
        // 目录等级大于0
        if ($this->directoryLevel > 0) {
            $base = $this->cachePath;
            for ($i = 0; $i < $this->directoryLevel; ++$i) {
                // key的两个字符作为文件夹
                if (($prefix = substr($key, $i + $i, 2)) !== false) {
                    $base .= DIRECTORY_SEPARATOR . $prefix;
                }
            }

            return $base . DIRECTORY_SEPARATOR . $key . $this->cacheFileSuffix;
        } else {
            return $this->cachePath . DIRECTORY_SEPARATOR . $key . $this->cacheFileSuffix;
        }
    }

    /**
     * 删除所有的缓存
     * Deletes all values from cache.
     * This is the implementation of the method declared in the parent class.
     * @return bool whether the flush operation was successful.
     */
    protected function flushValues()
    {
        $this->gc(true, false);

        return true;
    }

    /**
     * 删除过期的文件
     * Removes expired cache files.
     * @param bool $force whether to enforce the garbage collection regardless of [[gcProbability]].
     * Defaults to false, meaning the actual deletion happens with the probability as specified by [[gcProbability]].
     * @param bool $expiredOnly whether to removed expired cache files only.
     * If false, all cache files under [[cachePath]] will be removed.
     */
    /**
     * 删除过期的文件
     * @param  boolean $force       是否强制执行垃圾回收，不管几率参数
     * @param  boolean $expiredOnly 是否只删除过期文件
     * @return [type]               [description]
     */
    public function gc($force = false, $expiredOnly = true)
    {
        if ($force || mt_rand(0, 1000000) < $this->gcProbability) {
            $this->gcRecursive($this->cachePath, $expiredOnly);
        }
    }

    /**
     * Recursively removing expired cache files under a directory.
     * This method is mainly used by [[gc()]].
     * @param string $path the directory under which expired cache files are removed.
     * @param bool $expiredOnly whether to only remove expired cache files. If false, all files
     * under `$path` will be removed.
     */
    /**
     * [gcRecursive description]
     * @param  [type] $path        目录
     * @param  [type] $expiredOnly 是否只删除过期的
     * @return [type]              [description]
     */
    protected function gcRecursive($path, $expiredOnly)
    {
        if (($handle = opendir($path)) !== false) {
            while (($file = readdir($handle)) !== false) {
                // 只要开头是 . 的都继续，而不用判断是不是 . 或 .. 
                if ($file[0] === '.') {
                    continue;
                }
                $fullPath = $path . DIRECTORY_SEPARATOR . $file;
                if (is_dir($fullPath)) {
                    $this->gcRecursive($fullPath, $expiredOnly);
                    // 删除目录
                    if (!$expiredOnly) {
                        if (!@rmdir($fullPath)) {
                            // 获取错误信息
                            $error = error_get_last();
                            // 几率错误日志
                            Yii::warning("Unable to remove directory '{$fullPath}': {$error['message']}", __METHOD__);
                        }
                    }
                // 删除文件 过期文件
                } elseif (!$expiredOnly || $expiredOnly && @filemtime($fullPath) < time()) {
                    if (!@unlink($fullPath)) {
                        $error = error_get_last();
                        Yii::warning("Unable to remove file '{$fullPath}': {$error['message']}", __METHOD__);
                    }
                }
            }
            closedir($handle);
        }
    }
}
