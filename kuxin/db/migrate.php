<?php

namespace Kuxin\Db;

use Closure;
use Kuxin\Config;
use Kuxin\DI;

class Migrate
{

    protected $db = 'common';

    protected $comands = [];

    public function create(string $table, Closure $func, $engine = null)
    {
        if (!$table) {
            return false;
        }
        //执行回调函数
        $func();

        $field  = $this->combineCommand();

        $engine = $engine ?: Config::get('database.engine');

        if ($field) {
            $sql = "CREATE TABLE `{$table}` ({$field}) ENGINE={$engine} DEFAULT CHARSET=utf8;";
            return $this->executeSql($sql);
        }
    }

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


    public function drop(string $table)
    {
        if (!$table) {
            return false;
        }
        $sql = "DROP TABLE {$table}";
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

    protected function executeSql(string $sql)
    {
        $db = DI::DB($this->db);
        if ($db->execute($sql)) {
            return true;
        } else {
            throw new \Exception($db->errorInfo());
        }
    }
}
