<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db;

use yii\base\Object;

/**
 * 用来描述数据库表中的列属性
 * ColumnSchema class describes the metadata of a column in a database table.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
class ColumnSchema extends Object
{
    /**
     * 列名，字段名
     * @var string name of this column (without quotes).
     */
    public $name;
    /**
     * 是否允许null
     * @var bool whether this column can be null.
     */
    public $allowNull;
    /**
     * yii定义的抽象类型
     * @var string abstract type of this column. Possible abstract types include:
     * char, string, text, boolean, smallint, integer, bigint, float, decimal, datetime,
     * timestamp, time, date, binary, and money.
     */
    public $type;
    /**
     * 转化成php类型后的类型
     * @var string the PHP type of this column. Possible PHP types include:
     * `string`, `boolean`, `integer`, `double`.
     */
    public $phpType;
    /**
     * 数据库表列类型
     * @var string the DB type of this column. Possible DB types vary according to the type of DBMS.
     */
    public $dbType;
    /**
     * 默认值
     * @var mixed default value of this column
     */
    public $defaultValue;
    /**
     * 权举类型的权举值
     * @var array enumerable values. This is set only if the column is declared to be an enumerable type.
     */
    public $enumValues;
    /**
     * 长度
     * @var int display size of the column.
     */
    public $size;
    /**
     * 数值类型的长度，等于 $size
     * @var int precision of the column data, if it is numeric.
     */
    public $precision;
    /**
     * 小数位数
     * @var int scale of the column data, if it is numeric.
     */
    public $scale;
    /**
     * 是否是主键
     * @var bool whether this column is a primary key
     */
    public $isPrimaryKey;
    /**
     * 是否自动增长
     * @var bool whether this column is auto-incremental
     */
    public $autoIncrement = false;
    /**
     * 是否unsigned
     * @var bool whether this column is unsigned. This is only meaningful
     * when [[type]] is `smallint`, `integer` or `bigint`.
     */
    public $unsigned;
    /**
     * 注释
     * @var string comment of this column. Not all DBMS support this.
     */
    public $comment;


    /**
     * 将数据库的值转换成php类型
     * Converts the input value according to [[phpType]] after retrieval from the database.
     * If the value is null or an [[Expression]], it will not be converted.
     * @param mixed $value input value
     * @return mixed converted value
     */
    public function phpTypecast($value)
    {
        return $this->typecast($value);
    }

    /**
     * Converts the input value according to [[type]] and [[dbType]] for use in a db query.
     * If the value is null or an [[Expression]], it will not be converted.
     * @param mixed $value input value
     * @return mixed converted value. This may also be an array containing the value as the first element
     * and the PDO type as the second element.
     */
    public function dbTypecast($value)
    {
        // the default implementation does the same as casting for PHP, but it should be possible
        // to override this with annotation of explicit PDO type.
        return $this->typecast($value);
    }

    /**
     * 类型转换
     * Converts the input value according to [[phpType]] after retrieval from the database.
     * If the value is null or an [[Expression]], it will not be converted.
     * @param mixed $value input value
     * @return mixed converted value
     * @since 2.0.3
     */
    protected function typecast($value)
    {
        // 如果只为空 and type不是下面的几种直接返回null
        if ($value === '' && $this->type !== Schema::TYPE_TEXT && $this->type !== Schema::TYPE_STRING && $this->type !== Schema::TYPE_BINARY && $this->type !== Schema::TYPE_CHAR) {
            return null;
        }
        // 值为null or 类型和phpType类型一样 or 
        if ($value === null || gettype($value) === $this->phpType || $value instanceof Expression || $value instanceof Query) {
            return $value;
        }
        switch ($this->phpType) {
            case 'resource':
            case 'string':
                if (is_resource($value)) {
                    return $value;
                }
                if (is_float($value)) {
                    // ensure type cast always has . as decimal separator in all locales
                    return str_replace(',', '.', (string) $value);
                }
                return (string) $value;
            case 'integer':
                return (int) $value;
            case 'boolean':
                // treating a 0 bit value as false too
                // https://github.com/yiisoft/yii2/issues/9006
                return (bool) $value && $value !== "\0";
            case 'double':
                return (double) $value;
        }

        return $value;
    }
}
