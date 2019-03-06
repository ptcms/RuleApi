<?php

namespace Kuxin\Db;

use Kuxin\Model;
use ArrayAccess;

class ORM extends Model implements ArrayAccess
{
    /**
     * @var array
     */
    protected $attribute = [];
    /**
     * @var array
     */
    protected $original = [];

    /**
     * 获取单挑数据
     * @param mixed $id
     * @return $this
     */
    public function one($id = null)
    {
        $this->attribute = $this->original = $this->find($id);
        return $this;
    }

    /**
     * 保存数据
     */
    public function save()
    {
        $data = [];
        foreach ($this->attribute as $field => $value) {
            if ($field <> $this->getPk() && $value != $this->original[$field]) {
                $data[$field] = $value;
            }
        }
        if ($data) {
            $this->where([$this->getPk() => $this->original[$this->getPk()]])->update($data);
        }
    }

    /**
     * 修改器 设置数据对象的值
     * @access public
     * @param  string $name  名称
     * @param  mixed  $value 值
     * @return void
     */
    public function __set($name, $value)
    {
        if ($name != $this->getPk()) {
            $this->attribute[$name] = $value;
        }
    }

    /**
     * 获取器 获取数据对象的值
     * @access public
     * @param  string $name 名称
     * @return mixed
     */
    public function __get($name)
    {
        return $this->attribute[$name];
    }

    /**
     * 检测数据对象的值
     * @access public
     * @param  string $name 名称
     * @return boolean
     */
    public function __isset($name)
    {
        return isset($this->attribute[$name]);
    }

    /**
     * 销毁数据对象的值
     * @access public
     * @param  string $name 名称
     * @return void
     */
    public function __unset($name)
    {
        unset($this->attribute[$name]);
    }

    // ArrayAccess
    public function offsetSet($name, $value)
    {
        $this->__set($name, $value);
    }

    public function offsetUnset($name)
    {
        $this->__unset($name);
    }

    public function offsetExists($name)
    {
        return $this->__isset($name);
    }

    public function offsetGet($name)
    {
        return $this->__get($name);
    }

    public function __debugInfo()
    {
        return $this->attribute ?: $this;
    }
}