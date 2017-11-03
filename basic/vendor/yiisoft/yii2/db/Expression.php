<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db;

/**
 * 直接翻译过来就是表达式，真实的意图也就是让存放sql表达式的  
 * 目的是为了在生成sql的时候不给这个参数转义或者添加引号  
 * [参考](http://www.kkh86.com/it/yii2-adv/guide-adv-db-expression.html)  
 * 
 * Expression represents a DB expression that does not need escaping or quoting.
 *
 * When an Expression object is embedded within a SQL statement or fragment,
 * it will be replaced with the [[expression]] property value without any
 * DB escaping or quoting. For example,
 *
 * ```php
 * $expression = new Expression('NOW()');
 * $now = (new \yii\db\Query)->select($expression)->scalar();  // SELECT NOW();
 * echo $now; // prints the current date
 * ```
 *
 * Expression objects are mainly created for passing raw SQL expressions to methods of
 * [[Query]], [[ActiveQuery]], and related classes.
 *
 * An expression can also be bound with parameters specified via [[params]].
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class Expression extends \yii\base\Object
{
    /**
     * db 表达式 就是在sql中不需要转义或加引号的部分
     * @var string the DB expression
     */
    public $expression;
    /**
     * 参数 [':status' => $status] 的形式，用来替换拼接的sql中的 :status 
     * @var array list of parameters that should be bound for this expression.
     * The keys are placeholders appearing in [[expression]] and the values
     * are the corresponding parameter values.
     */
    public $params = [];


    /**
     * Constructor.
     * @param string $expression the DB expression
     * @param array $params parameters
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($expression, $params = [], $config = [])
    {
        $this->expression = $expression;
        $this->params = $params;
        parent::__construct($config);
    }

    /**
     * String magic method
     * @return string the DB expression
     */
    public function __toString()
    {
        return $this->expression;
    }
}
