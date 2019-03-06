<?php

namespace Kuxin\Db;

use Closure;
use Kuxin\Config;
use Kuxin\DI;

class Migrate
{

    /** @var Mysql $db */
    protected $db;

    public function __construct($db = 'common')
    {
        $this->setDb($db);
    }

    /**
     * @param null $db
     * @throws \Exception
     */
    public function setDb($db): void
    {
        if (is_string($db)) {
            $this->db = DI::DB($db);
        } else if ($db instanceof Mysql) {
            $this->db = $db;
        } else {
            throw new \Exception('param $db error');
        }
    }

    /**
     * @param string $table
     * @param \Closure $func
     * @param string $engine
     * @return bool
     * @throws \Exception
     */
    public function create(string $table, Closure $func, $engine = 'innodb')
    {
        if (!$table) {
            return false;
        }
        //执行回调函数
        $func();

        $field = $this->combineCommand();

        $engine = $engine ?: Config::get('database.engine', 'innodb');

        if ($field) {
            $sql = "CREATE TABLE IF NOT EXISTS `{$table}` ({$field})  ENGINE={$engine} DEFAULT CHARSET=utf8;";
            return $this->executeSql($sql);
        }
    }

    /**
     * @param string $table
     * @param \Closure $func
     * @return bool
     * @throws \Exception
     */
    public function alter(string $table, Closure $func)
    {
        if (!$table) {
            return false;
        }
        //执行回调函数
        $func();

        $this->comands = array_map(function ($k) use ($table) {
            return 'alter table ' . $table . ' ' . trim($k, ';') . ';';
        }, $this->comands);
        $sql           = $this->combineCommand();
        return $this->executeSql($sql);
    }


    /**
     * @param string $table
     * @return bool
     * @throws \Exception
     */
    public function drop(string $table)
    {
        if (!$table) {
            return false;
        }
        $sql = "DROP TABLE IF EXISTS {$table}";
        return $this->executeSql($sql);
    }

    public function addComand(string $string): void
    {
        $this->comands[] = trim($string, ',');
    }

    /**
     * @return string
     */
    protected function combineCommand(): string
    {
        $command       = implode(',', $this->comands);
        $this->comands = [];
        return $command;
    }

    /**
     * @param string $sql
     * @return bool
     * @throws \Exception
     */
    protected function executeSql(string $sql)
    {
        if ($this->db->execute($sql)) {
            return true;
        } else {
            throw new \Exception($this->db->errorInfo() . PHP_EOL . $this->db->getRealSql($sql));
        }
    }

    /**
     * @param string $sql
     * @return array
     * @throws \Exception
     */
    protected function fetchAll(string $sql)
    {
        if (($records = $this->db->fetchAll($sql)) !== false){
            return $records;
        }else{
            throw new \Exception($this->db->errorInfo() . PHP_EOL . $this->db->getRealSql($sql));
        }
    }
}
