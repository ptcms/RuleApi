<?php

namespace Kuxin\Db;

use Kuxin\Config;
use Kuxin\Registry;

class Mysql
{
    /**
     * 数据库连接ID
     *
     * @var object
     */
    public $db_link;
    /**
     * 事务处理开启状态
     *
     * @var boolean
     */
    protected $Transactions;

    /**
     * 数据库语句
     *
     * @var array
     */
    public $sql = '';

    /**
     * debug开关
     *
     * @var bool
     */
    protected $debug;

    /**
     * PDO操作实例
     *
     * @var \PDOStatement
     */
    protected $PDOStatement;

    /**
     * 构造函数
     * 用于初始化运行环境,或对基本变量进行赋值
     *
     * @param array $params 数据库连接参数,如主机名,数据库用户名,密码等
     */
    public function __construct(array $params)
    {
        //连接数据库
        $params['charset'] = empty($params['charset']) ? 'utf8' : $params['charset'];
        try {
            $this->db_link = new \PDO("mysql:host={$params['host']};port={$params['port']};dbname={$params['name']}", $params['user'], $params['pwd'], []);
        } catch (\Exception $e) {
            trigger_error($e->getMessage());
        }
        $this->db_link->query("SET NAMES {$params['charset']}");
        $this->db_link->query("SET sql_mode='NO_ENGINE_SUBSTITUTION'");
        if (!$this->db_link) {
            trigger_error($params['driver'] . ' Server connect fail! <br/>Error Message:' . $this->db_link->error() . '<br/>Error Code:' . $this->db_link->errno(), E_USER_ERROR);
        }
        $this->debug = Config::get('database.debug', Config::get('app.debug', false));
    }

    /**
     * 执行SQL语句
     * SQL语句执行函数
     *
     * @access public
     * @param string $sql SQL语句内容
     * @return bool
     */
    public function execute(string $sql, array $bindparams = [])
    {
        //参数分析
        if (!$sql) {
            return false;
        }

        //释放前次的查询结果
        if (!empty($this->PDOStatement) && $this->PDOStatement->queryString != $sql) {
            $this->free();
        }

        $this->PDOStatement = $this->db_link->prepare($sql);
        foreach ($bindparams as $k => $v) {
            $this->PDOStatement->bindValue($k, $bindparams[$k]);
        }
        $realSql = $this->getRealSql($sql, $bindparams);
        if ($this->debug) {
            $t      = microtime(true);
            $result = $this->PDOStatement->execute();
            $t      = number_format(microtime(true) - $t, 5);
            Registry::merge('_sql', $t . ' - ' . $realSql);
        } else {
            $result = $this->PDOStatement->execute();
        }
        $this->sql = $realSql;
        Registry::setInc('_sqlnum');
        return $result;
    }

    /**
     * 获取数据库错误描述信息
     *
     * @access public
     * @return string
     */
    public function errorInfo()
    {
        if ($this->PDOStatement) {
            $error = $this->PDOStatement->errorInfo();
        } else {
            $error = $this->db_link->errorInfo();
        }
        if ($error['0'] == '0000') {
            return '';
        } else {
            return $error['2'];
        }
    }

    /**
     * 获取数据库错误信息代码
     *
     * @access public
     * @return int
     */
    public function errorCode()
    {
        return $this->PDOStatement->errorCode();
    }

    /**
     * 通过一个SQL语句获取一行信息(字段型)
     *
     * @access public
     * @param string $sql SQL语句内容
     * @return mixed
     */
    public function fetch(string $sql, array $bindParams = [])
    {
        $result = $this->execute($sql, $bindParams);
        if (!$result) {
            return false;
        }

        $myrow = $this->PDOStatement->fetch(\PDO::FETCH_ASSOC);

        $this->free();

        if (!$myrow)
            return null;

        return $myrow;
    }

    /**
     * 通过一个SQL语句获取全部信息(字段型)
     *
     * @access public
     * @param string $sql SQL语句
     * @return array|mixed
     */
    public function fetchAll(string $sql, array $bindParams = [])
    {
        $result = $this->execute($sql, $bindParams);

        if (!$result) {
            return false;
        }

        $myrow = $this->PDOStatement->fetchAll(\PDO::FETCH_ASSOC);

        $this->free();

        if (!$myrow) {
            return [];
        }

        return $myrow;
    }

    /**
     * 获取insert_id
     *
     * @access public
     * @return int
     */
    public function insertId()
    {
        return $this->db_link->lastInsertId();
    }

    /**
     * 开启事务处理
     *
     * @access public
     * @return boolean
     */
    public function startTrans()
    {
        if ($this->Transactions == false) {
            $this->db_link->beginTransaction();
            $this->Transactions = true;
        }
        return true;
    }

    /**
     * 提交事务处理
     *
     * @access public
     * @return boolean
     */
    public function commit()
    {

        if ($this->Transactions == true) {
            if ($this->db_link->commit()) {
                $this->Transactions = false;
            }
        }

        return true;
    }


    /**
     * 事务回滚
     */
    public function rollback()
    {
        if ($this->Transactions == true) {
            $this->db_link->rollBack();
            $this->Transactions = false;
        }
    }

    /**
     * 关闭数据库连接
     */
    public function __destruct()
    {
        $this->free();
        if ($this->db_link == true) {
            $this->db_link = null;
        }
    }

    /**
     * SQL指令安全过滤
     *
     * @access public
     * @param string $str SQL字符串
     * @return string
     */
    public function quote($str)
    {
        return $this->db_link->quote($str);
    }

    /**
     * 根据参数绑定组装最终的SQL语句 便于调试
     *
     * @access public
     * @param string $sql  带参数绑定的sql语句
     * @param array  $bind 参数绑定列表
     * @return string
     */
    public function getRealSql($sql, array $bind = [])
    {
        foreach ($bind as $key => $val) {
            $val = $this->quote($val);
            // 判断占位符
            $sql = str_replace($key, $val, $sql);
        }
        return $sql;
    }

    /**
     * 释放查询结果
     *
     * @access public
     */
    public function free()
    {
        $this->PDOStatement = null;
    }

    /**
     * 返回最后插入行的ID或序列值
     *
     * @return string
     */
    public function lastInsertId()
    {
        return $this->db_link->lastInsertId();
    }

    /**
     * 返回受上一个 SQL 语句影响的行数
     *
     * @return bool|int
     */
    public function rowCount()
    {
        if ($this->PDOStatement) {
            return $this->PDOStatement->rowCount();
        } else {
            return false;
        }
    }

    public function lastSql()
    {
        $sql = $this->sql;
        if ($sql) {
            return $sql;
        } else {
            return '没有语句,请执行sql或者开启debug';
        }
    }
}