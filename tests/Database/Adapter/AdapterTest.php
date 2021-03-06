<?php

namespace Phoenix\Tests\Database\Adapter;

use Phoenix\Database\Adapter\MysqlAdapter;
use Phoenix\Database\Adapter\PgsqlAdapter;
use Phoenix\Database\Adapter\SqliteAdapter;
use Phoenix\Tests\Mock\Database\FakePdo;
use PHPUnit_Framework_TestCase;

class AdapterTest extends PHPUnit_Framework_TestCase
{
    public function testMysqlAdapter()
    {
        $pdo = new FakePdo();
        $adapter = new MysqlAdapter($pdo);
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $adapter->getQueryBuilder());
    }
    
    public function testPgsqlAdapter()
    {
        $pdo = new FakePdo();
        $adapter = new PgsqlAdapter($pdo);
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $adapter->getQueryBuilder());
    }
    
    public function testSqliteAdapter()
    {
        $pdo = new FakePdo();
        $adapter = new SqliteAdapter($pdo);
        $this->assertInstanceOf('\Phoenix\Database\QueryBuilder\QueryBuilderInterface', $adapter->getQueryBuilder());
    }
}
