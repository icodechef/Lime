<?php

namespace Lime;

/**
 * PDO 数据库驱动
 *
 * @author icodechef <dengman2010@gmail.com>
 * @since 1.0
 */
class Pdo
{
    /**
     * 配置
     *
     * @var array
     */
    protected $config = [];

    /**
     * 数据库连接句柄
     *
     * @var resource
     */
    protected $pdo;

    /**
     * 表前缀
     *
     * @var string
     */
    protected $prefix = '';

    /**
     * 构造函数
     *
     * @param array $config
     */
    public function __construct($config)
    {
        if (! extension_loaded('pdo')) {
            throw new \Exception('The PDO extension is required but the extension is not loaded');
        }

        if (empty($config['dsn'])) {
            throw new \Exception('config[dsn] cannot be empty.');
        }

        $config['options'] = isset($config['options']) ? $config['options'] : [];
        $this->config = $config;

        $this->connect();
    }

    /**
     * 链接数据库
     */
    public function connect()
    {
        if ($this->isConnected()) {
            return;
        }

        try
        {
            $this->pdo = new \PDO(
                $this->config['dsn'],
                $this->config['username'],
                $this->config['password'],
                $this->config['options']
            );
        } catch (\PDOException $e) {
            $error = "Connect Error: " . $e->getCode() . ':' . $e->getMessage();
            throw new \Exception($error);
            return false;
        }

        // 防止打印输出对象时暴露此信息
        unset($this->config['username'], $this->config['password']);

        // Leave column names as returned by the database driver
        $this->pdo->setAttribute(\PDO::ATTR_CASE, \PDO::CASE_NATURAL);
        // 异常抛出
        $this->pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
        // 不转换NULL或者空字符串
        $this->pdo->setAttribute(\PDO::ATTR_ORACLE_NULLS, \PDO::NULL_NATURAL);
        // 不将数值转换为字符串
        $this->pdo->setAttribute(\PDO::ATTR_STRINGIFY_FETCHES, false);
        // 禁用模拟预处理语句, 主要用于防SQL注入
        $this->pdo->setAttribute(\PDO::ATTR_EMULATE_PREPARES, false);
        // 返回key-value数组, 不包含index的
        $this->pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

        // 防 SQL 注入, 应该设置数据库的编码
        if (! empty($this->config['charset'])) {
            $this->setCharset($this->config['charset']);
        }

        if (! empty($this->config['prefix'])) {
            $this->prefix = $this->config['prefix'];
        }
    }

    /**
     * 预处理语句并返回一个PDOStatement对象
     * 使用预处理语句的另外一个好处是, 
     * 如果你在同一会话中多次执行相同的语句, 只被解析和编译一次, 提高了速度
     *
     * @param  string $query
     * @param  array  $driverOptions
     * @return PDOStatement
     */
    public function prepare($query, $driverOptions = [])
    {
        return $this->pdo->prepare($query, $driverOptions);
    }

    /**
     * 执行 SQL 语句并返回受影响的行数
     *
     * @param mixed $query SQL 语句
     * @return integer 修改或删除的行数
     */
    public function exec($query)
    {
        $query = $this->queryString($query);

        try
        {
            $affected = $this->pdo->exec($query);

            if ($affected === false) {
                $errorInfo = $this->pdo->errorInfo();
                throw new \Exception($errorInfo[2]);
            }
        } catch (\PDOException $e) {
            $error = "Error performing query: {$query} : " . $e->getCode() . ':' . $e->getMessage();
            throw new \Exception($error);
        }

        return $affected;
    }

    /**
     * 执行 SQL 语句并并返回一个 PDOStatement 对象
     *
     * @param  string  $query       sql语句
     * @param  array   $parameters
     * @return PDOStatement
     */
    public function query($query, $parameters = null)
    {
        $query = $this->queryString($query);

        if (is_array($parameters)) {
            foreach ($parameters as $name => $value) {
                if (!is_int($name) && !preg_match('/^:/', $name)) {
                    $newName = ":$name";
                    unset($parameters[$name]);
                    $parameters[$newName] = $value;
                }
            }
        } else {
            $parameters = [];
        }

        try
        {
            $stmt = $this->pdo->prepare($query);
            $stmt->execute($parameters);
        } catch (\Exception $e) {
            $error = "Error performing query: {$query} : " . $e->getCode() . ':' . $e->getMessage();
            throw new \Exception($error);
        }

        return $stmt;
    }

    /**
     * 是否已经链接
     *
     * @return bool
     */
    public function isConnected()
    {
        return ((bool) ($this->pdo instanceof \PDO));
    }

    /**
     * 设置字符集
     *
     * @param string $charset 字符集
     */
    public function setCharset($charset)
    {
        if (! $this->isConnected()) {
            return;
        }

        $this->pdo->exec('SET NAMES '.$this->quote($charset));
    }

    /**
     * 转义一个字符串
     *
     * @param string $value
     * @return string
     */
    public function quote($value)
    {
        if (is_int($value) || is_float($value)) {
            return $value;
        }

        return $this->pdo->quote($value);
    }

    /**
     * 解析当前查询语句, 并返回解析后结果
     *
     * @param string $query
     */
    public function queryString($query)
    {
        return preg_replace('/{{(.*?)}}/', $this->prefix . '$1', $query);
    }

    /**
     * 返回处理后的 SQL 语句，主要用于调试
     *
     * @param string $query
     */
    public function SQL($query, $parameters)
    {
        $query = $this->queryString($query);

        if (is_array($parameters)) {
            foreach ($parameters as $name => $value) {
                if (!is_int($name) && !preg_match('/^:/', $name)) {
                    $newName = ":$name";
                    unset($parameters[$name]);
                    $parameters[$newName] = $value;
                }
            }
        } else {
            $parameters = [];
        }

        $keys = [];
        $values = [];
        
        foreach ($parameters as $key=>$value) {
            if ( is_string($key) ) {
                $keys[] = '/'.$key.'/';
            } else {
                $keys[] = '/[?]/';
            }
            
            $values[] = $this->quote($value);
        }
        
        $query = preg_replace($keys, $values, $query, 1, $count);

        return $query;
    }

    /**
     * 添加表前缀
     *
     * @param string $name
     * @return string
     */
    public function tableName($name)
    {
        return '`' . $this->prefix . $name . '`';
    }

    /**
     * 返回最后插入行的ID或序列值
     *
     * @return string
     */
    public function lastInsertId($name = NULL)
    {
        return $this->pdo->lastInsertId($name);
    }

    /**
     * 事务处理
     *
     * @return string
     */
    public function transaction($query, $parameters = null)
    {
        $this->pdo->beginTransaction();

        try
        {
            $stmt = $this->query($query, $parameters);
            $this->pdo->commit();
        } catch (\Exception $e) {
            $this->pdo->rollBack();
            throw $e;
        }

        return $stmt;
    }
    
    /**
     * 开始一个事务
     */
    public function beginTransaction()
    {
        $this->pdo->beginTransaction();
    }

    /**
     * 提交事务
     */
    public function commit()
    {
        $this->pdo->commit();
    }

    /**
     * 回滚事务
     */
    public function rollBack()
    {
        $this->pdo->rollBack();
    }

    /**
     * 关闭链接
     *
     * @return void
     */
    public function close()
    {
        $this->pdo = null;
    }

    public function __destruct()
    {
        $this->close();
    }
}