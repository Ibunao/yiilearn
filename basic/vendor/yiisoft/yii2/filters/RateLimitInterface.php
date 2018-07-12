<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\filters;

/**
 * [参考文档](https://www.yiichina.com/doc/guide/2.0/rest-rate-limiting)  
 * RateLimitInterface is the interface that may be implemented by an identity object to enforce rate limiting.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
interface RateLimitInterface
{
    /**
     * 返回一段时间内允许请求的最大次数
     * Returns the maximum number of allowed requests and the window size.
     * @param \yii\web\Request $request the current request
     * @param \yii\base\Action $action the action to be executed
     * @return array an array of two elements. The first element is the maximum number of allowed requests,
     * and the second element is the size of the window in seconds.
     * return [最大请求数量, 时间段]
     */
    public function getRateLimit($request, $action);

    /**
     * 获取允许请求的数量和时间戳
     * Loads the number of allowed requests and the corresponding timestamp from a persistent storage.
     * @param \yii\web\Request $request the current request
     * @param \yii\base\Action $action the action to be executed
     * @return array an array of two elements. The first element is the number of allowed requests,
     * and the second element is the corresponding UNIX timestamp.
     * return [剩余请求数量, 上次请求的时间戳]
     */
    public function loadAllowance($request, $action);

    /**
     * 将允许访问的次数和时间戳保存
     * Saves the number of allowed requests and the corresponding timestamp to a persistent storage.
     * @param \yii\web\Request $request the current request
     * @param \yii\base\Action $action the action to be executed
     * @param int $allowance the number of allowed requests remaining.
     * @param int $timestamp the current timestamp.
     */
    public function saveAllowance($request, $action, $allowance, $timestamp);
}
