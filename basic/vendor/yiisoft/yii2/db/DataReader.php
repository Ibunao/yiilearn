<?php
/**
 * @link http://www.yiiframework.com/
 * @copyright Copyright (c) 2008 Yii Software LLC
 * @license http://www.yiiframework.com/license/
 */

namespace yii\db;

use yii\base\InvalidCallException;

/**
 * DataReader represents a forward-only stream of rows from a query result set.
 *
 * To read the current row of data, call [[read()]]. The method [[readAll()]]
 * returns all the rows in a single array. Rows of data can also be read by
 * iterating through the reader. For example,
 *
 * ```php
 * $command = $connection->createCommand('SELECT * FROM post');
 * $reader = $command->query();
 *
 * while ($row = $reader->read()) {
 *     $rows[] = $row;
 * }
 *
 * // equivalent to:
 * foreach ($reader as $row) {
 *     $rows[] = $row;
 * }
 *
 * // equivalent to:
 * $rows = $reader->readAll();
 * ```
 *
 * Note that since DataReader is a forward-only stream, you can only traverse it once.
 * Doing it the second time will throw an exception.
 *
 * It is possible to use a specific mode of data fetching by setting
 * [[fetchMode]]. See the [PHP manual](http://www.php.net/manual/en/function.PDOStatement-setFetchMode.php)
 * for more details about possible fetch mode.
 *
 * @property int $columnCount The number of columns in the result set. This property is read-only.
 * @property int $fetchMode Fetch mode. This property is write-only.
 * @property bool $isClosed Whether the reader is closed or not. This property is read-only.
 * @property int $rowCount Number of rows contained in the result. This property is read-only.
 *
 * @author Qiang Xue <qiang.xue@gmail.com>
 * @since 2.0
 */
/**
 * pdo的相关方法参考  
 * https://www.cnblogs.com/vlone/p/4592846.html
 */
class DataReader extends \yii\base\Object implements \Iterator, \Countable
{
    /**
     * @var \PDOStatement the PDOStatement associated with the command
     */
    // pdo对象
    private $_statement;
    private $_closed = false;
    private $_row;
    private $_index = -1;


    /**
     * Constructor.
     * @param Command $command the command generating the query result
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct(Command $command, $config = [])
    {
        $this->_statement = $command->pdoStatement;
        // 设置获取 从结果集中获取以列名为索引的关联数组。
        // https://www.cnblogs.com/vlone/p/4592846.html
        $this->_statement->setFetchMode(\PDO::FETCH_ASSOC);
        parent::__construct($config);
    }

    /**
     * 使用fetched的时候将值绑定到给指定的变量
     * Binds a column to a PHP variable.
     * When rows of data are being fetched, the corresponding column value
     * will be set in the variable. Note, the fetch mode must include PDO::FETCH_BOUND.
     * @param int|string $column Number of the column (1-indexed) or name of the column
     * in the result set. If using the column name, be aware that the name
     * should match the case of the column, as returned by the driver.
     * @param mixed $value Name of the PHP variable to which the column will be bound.
     * @param int $dataType Data type of the parameter
     * @see http://www.php.net/manual/en/function.PDOStatement-bindColumn.php
     */
    public function bindColumn($column, &$value, $dataType = null)
    {
        if ($dataType === null) {
            $this->_statement->bindColumn($column, $value);
        } else {
            $this->_statement->bindColumn($column, $value, $dataType);
        }
    }

    /**
     * 设置pdo读取数据的格式
     * Set the default fetch mode for this statement
     * @param int $mode fetch mode
     * @see http://www.php.net/manual/en/function.PDOStatement-setFetchMode.php
     */
    public function setFetchMode($mode)
    {
        // 获取参数
        $params = func_get_args();
        // 设置pdo读取格式
        call_user_func_array([$this->_statement, 'setFetchMode'], $params);
    }

    /**
     * fetch，一次获取一行
     * Advances the reader to the next row in a result set.
     * @return array the current row, false if no more row available
     */
    public function read()
    {
        return $this->_statement->fetch();
    }

    /**
     * 一次读取一行，并获取 给定索引列的值
     * Returns a single column from the next row of a result set.
     * @param int $columnIndex zero-based column index
     * @return mixed the column of the current row, false if no more rows available
     */
    public function readColumn($columnIndex)
    {
        return $this->_statement->fetchColumn($columnIndex);
    }

    /**
     * 将数据对去到对象中
     * Returns an object populated with the next row of data.
     * @param string $className class name of the object to be created and populated  类名
     * @param array $fields Elements of this array are passed to the constructor 赋值给构造函数的字段
     * @return mixed the populated object, false if no more row of data available
     */
    public function readObject($className, $fields)
    {
        return $this->_statement->fetchObject($className, $fields);
    }

    /**
     * 返回一个包含结果集中所有行的数组
     * Reads the whole result set into an array.
     * @return array the result set (each array element represents a row of data).
     * An empty array will be returned if the result contains no row.
     */
    public function readAll()
    {
        return $this->_statement->fetchAll();
    }

    /**
     * 下一行
     * Advances the reader to the next result when reading the results of a batch of statements.
     * This method is only useful when there are multiple result sets
     * returned by the query. Not all DBMS support this feature.
     * @return bool Returns true on success or false on failure.
     */
    public function nextResult()
    {
        if (($result = $this->_statement->nextRowset()) !== false) {
            $this->_index = -1;
        }

        return $result;
    }

    /**
     * 关闭游标，使语句能再次被执行
     * Closes the reader.
     * This frees up the resources allocated for executing this SQL statement.
     * Read attempts after this method call are unpredictable.
     */
    public function close()
    {
        $this->_statement->closeCursor();
        $this->_closed = true;
    }

    /**
     * whether the reader is closed or not.
     * @return bool whether the reader is closed or not.
     */
    public function getIsClosed()
    {
        return $this->_closed;
    }

    /**
     * Returns the number of rows in the result set.
     * Note, most DBMS may not give a meaningful count.
     * In this case, use "SELECT COUNT(*) FROM tableName" to obtain the number of rows.
     * @return int number of rows contained in the result.
     */
    public function getRowCount()
    {
        return $this->_statement->rowCount();
    }
/**
 * Countable接口的实现部分，上对象可以count
 */
    /**
     * 获取数据总行数
     * Returns the number of rows in the result set.
     * This method is required by the Countable interface.
     * Note, most DBMS may not give a meaningful count.
     * In this case, use "SELECT COUNT(*) FROM tableName" to obtain the number of rows.
     * @return int number of rows contained in the result.
     */
    public function count()
    {
        return $this->getRowCount();
    }

    /**
     * 返回列数
     * Returns the number of columns in the result set.
     * Note, even there's no row in the reader, this still gives correct column number.
     * @return int the number of columns in the result set.
     */
    public function getColumnCount()
    {
        return $this->_statement->columnCount();
    }
/**
 * Iterator接口 迭代器的实现部分，上对象可以foreach遍历
 */
    /**
     * Resets the iterator to the initial state.
     * This method is required by the interface [[\Iterator]].
     * @throws InvalidCallException if this method is invoked twice
     */
    public function rewind()
    {
        if ($this->_index < 0) {
            $this->_row = $this->_statement->fetch();
            $this->_index = 0;
        } else {
            throw new InvalidCallException('DataReader cannot rewind. It is a forward-only reader.');
        }
    }

    /**
     * Returns the index of the current row.
     * This method is required by the interface [[\Iterator]].
     * @return int the index of the current row.
     */
    public function key()
    {
        return $this->_index;
    }

    /**
     * Returns the current row.
     * This method is required by the interface [[\Iterator]].
     * @return mixed the current row.
     */
    public function current()
    {
        return $this->_row;
    }

    /**
     * Moves the internal pointer to the next row.
     * This method is required by the interface [[\Iterator]].
     */
    public function next()
    {
        $this->_row = $this->_statement->fetch();
        $this->_index++;
    }

    /**
     * Returns whether there is a row of data at current position.
     * This method is required by the interface [[\Iterator]].
     * @return bool whether there is a row of data at current position.
     */
    public function valid()
    {
        return $this->_row !== false;
    }
}
