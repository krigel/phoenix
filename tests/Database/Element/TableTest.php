<?php

namespace Phoenix\Tests\Database\Element;

use Phoenix\Database\Element\Table;
use PHPUnit_Framework_TestCase;

class TableTest extends PHPUnit_Framework_TestCase
{
    public function testDefaultConstruct()
    {
        $table = new Table('test');
        $columns = $table->getColumns();
        $this->assertCount(1, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf('\Phoenix\Database\Element\Column', $column);
        }
        $idColumn = $table->getColumn('id');
        $this->assertInstanceOf('\Phoenix\Database\Element\Column', $idColumn);
        $this->assertEquals('id', $idColumn->getName());
        $this->assertEquals('integer', $idColumn->getType());
        $this->assertFalse($idColumn->allowNull());
        $this->assertNull($idColumn->getDefault());
        $this->assertTrue($idColumn->isSigned());
        $this->assertNull($idColumn->getLength());
        $this->assertNull($idColumn->getDecimals());
        $this->assertTrue($idColumn->isAutoincrement());
        $primaryColumns = $table->getPrimaryColumns();
        $this->assertCount(1, $primaryColumns);
        foreach ($primaryColumns as $primaryColumn) {
            $this->assertTrue(is_string($primaryColumn));
        }
    }
    
    public function testNoPrimaryKeyConstruct()
    {
        $table = new Table('test', false);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('title', 'string'));
        
        $columns = $table->getColumns();
        $this->assertCount(1, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf('\Phoenix\Database\Element\Column', $column);
        }
        $this->assertCount(0, $table->getPrimaryColumns());
    }
    
    public function testAddColumn()
    {
        $table = new Table('test');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('total', 'int'));
    }
    
    public function testAddIndex()
    {
        $table = new Table('test');
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex('title', 'unique'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(['title', 'alias']));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(['bodytext', 'fulltext']));
    }
    
    public function testGetters()
    {
        $table = new Table('test', false);
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('total', 'int'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addColumn('bodytext', 'text'));
        
        $columns = $table->getColumns();
        $this->assertCount(3, $columns);
        foreach ($columns as $column) {
            $this->assertInstanceOf('\Phoenix\Database\Element\Column', $column);
        }
        $this->assertInstanceOf('\Phoenix\Database\Element\Column', $table->getColumn('title'));
        
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex('title', 'unique'));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(['title', 'alias']));
        $this->assertInstanceOf('\Phoenix\Database\Element\Table', $table->addIndex(['bodytext', 'fulltext']));
        
        $indexes = $table->getIndexes();
        $this->assertCount(3, $indexes);
        foreach ($indexes as $index) {
            $this->assertInstanceOf('\Phoenix\Database\Element\Index', $index);
        }
        
        $this->setExpectedException('\Exception', 'Column "unknown_column" not found');
        $table->getColumn('unknown_column');
    }
}