<?php

namespace Phoenix\Database\Element;

use Phoenix\Exception\InvalidArgumentValueException;

class Index
{
    const TYPE_NORMAL = '';
    const TYPE_UNIQUE = 'UNIQUE';
    const TYPE_FULLTEXT = 'FULLTEXT';
    
    const METHOD_DEFAULT = '';
    const METHOD_BTREE = 'BTREE';
    const METHOD_HASH = 'HASH';
    
    private $columns = [];
    
    private $name;
    
    private $type;
    
    private $method;
    
    /**
     * @param string|array $columns name(s) of column(s)
     * @param string $name name of index
     * @param string $type type of index (unique, fulltext) default ''
     * @param string $method method of index (btree, hash) default ''
     * @throws InvalidArgumentValueException if index type or index method is not allowed
     */
    public function __construct($columns, $name, $type = self::TYPE_NORMAL, $method = self::METHOD_DEFAULT)
    {
        $this->name = $name;
        $this->type = strtoupper($type);
        if (!in_array($this->type, [self::TYPE_NORMAL, self::TYPE_UNIQUE, self::TYPE_FULLTEXT])) {
            throw new InvalidArgumentValueException('Index type "' . $type . '" is not allowed');
        }
        
        $this->method = strtoupper($method);
        if (!in_array($this->method, [self::METHOD_DEFAULT, self::METHOD_BTREE, self::METHOD_HASH])) {
            throw new InvalidArgumentValueException('Index method "' . $method . '" is not allowed');
        }
        
        if (is_array($columns)) {
            $this->columns = $columns;
        } elseif (is_string($columns)) {
            $this->columns[] = $columns;
        }
    }
    
    /**
     * @return string
     */
    public function getName()
    {
        return $this->name;
    }

    /**
     * @return array
     */
    public function getColumns()
    {
        return $this->columns;
    }

    /**
     * @return string
     */
    public function getType()
    {
        return $this->type ? $this->type . ' INDEX' : 'INDEX';
    }
    
    /**
     * @return string
     */
    public function getMethod()
    {
        return $this->method ? 'USING ' . $this->method : '';
    }
}
