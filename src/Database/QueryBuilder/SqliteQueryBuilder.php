<?php

namespace Phoenix\Database\QueryBuilder;

use Phoenix\Database\Element\Column;
use Phoenix\Database\Element\Index;
use Phoenix\Database\Element\Table;

class SqliteQueryBuilder extends CommonQueryBuilder implements QueryBuilderInterface
{
    protected $typeMap = [
        Column::TYPE_STRING => 'varchar(%d)',
        Column::TYPE_INTEGER => 'integer',
        Column::TYPE_BOOLEAN => 'boolean',
        Column::TYPE_TEXT => 'text',
        Column::TYPE_DATETIME => 'datetime',
        Column::TYPE_UUID => 'char(36)',
        Column::TYPE_JSON => 'text',
        Column::TYPE_CHAR => 'char(%d)',
        Column::TYPE_DECIMAL => 'decimal(%d,%d)',
    ];
    
    protected $defaultLength = [
        Column::TYPE_STRING => 255,
        Column::TYPE_CHAR => 255,
        Column::TYPE_DECIMAL => [10, 0],
    ];
    
    /**
     * generates create table queries for sqlite
     * @param Table $table
     * @return array list of queries
     */
    public function createTable(Table $table)
    {
        $queries = [];
        $queries[] = $this->createTableQuery($table);
        foreach ($table->getIndexes() as $index) {
            $queries[] = $this->createIndex($index, $table);
        }
        return $queries;
    }
    
    /**
     * generates drop table query for sqlite
     * @param Table $table
     * @return array list of queries
     */
    public function dropTable(Table $table)
    {
        return ['DROP TABLE ' . $this->escapeString($table->getName())];
    }
    
    /**
     * generates rename table queries for sqlite
     * @param Table $table
     * @param string $newTableName
     * @return array list of queries
     */
    public function renameTable(Table $table, $newTableName)
    {
        return ['ALTER TABLE ' . $this->escapeString($table->getName()) . ' RENAME TO ' . $this->escapeString($newTableName) . ';'];
    }
    
    /**
     * @param Table $table
     * @return array list of queries
     */
    public function alterTable(Table $table)
    {
        $queries = [];
        
        $columns = $table->getColumns();
        unset($columns['id']);
        if (!empty($columns)) {
            $query = 'ALTER TABLE ' . $this->escapeString($table->getName()) . ' ';
            $columnList = [];
            foreach ($columns as $column) {
                $columnList[] = 'ADD COLUMN ' . $this->createColumn($column, $table);
            }
            $query .= implode(',', $columnList) . ';';
            $queries[] = $query;
        }
        
        return $queries;
    }
    
    protected function createColumn(Column $column, Table $table)
    {
        $col = $this->escapeString($column->getName()) . ' ' . $this->createType($column);
        $col .= $column->isAutoincrement() && in_array($column->getName(), $table->getPrimaryColumns()) ? ' PRIMARY KEY AUTOINCREMENT' : '';
        $col .= $column->allowNull() ? '' : ' NOT NULL';
        if ($column->getDefault() !== null && $column->getDefault() !== '') {
            $col .= ' DEFAULT ';
            if (in_array($column->getType(), [Column::TYPE_INTEGER, Column::TYPE_BOOLEAN])) {
                $col .= intval($column->getDefault());
            } else {
                $col .= "'" . $column->getDefault() . "'";
            }
        } elseif ($column->allowNull() && $column->getDefault() === null) {
            $col .= ' DEFAULT NULL';
        }
        return $col;
    }
    
    protected function createPrimaryKey(Table $table)
    {
        if (empty($table->getPrimaryColumns())) {
            return '';
        }
        
        $primaryKeys = [];
        foreach ($table->getPrimaryColumns() as $name) {
            $column = $table->getColumn($name);
            if (!$column->isAutoincrement()) {
                $primaryKeys[] = $this->escapeString($column->getName());
            }
        }
        if (empty($primaryKeys)) {
            return '';
        }
        return ',PRIMARY KEY (' . implode(',', $primaryKeys) . ')';
    }
    
    private function createIndex(Index $index, Table $table)
    {
        $columns = [];
        foreach ($index->getColumns() as $column) {
            $columns[] = $this->escapeString($table->getColumn($column)->getName());
        }
        $query = 'CREATE ' . $index->getType() . ' ' . $this->escapeString($index->getName()) . ' ON ' . $this->escapeString($table->getName()) . ' (' . implode(',', $columns) . ');';
        return $query;
    }
    
    public function escapeString($string)
    {
        return '"' . $string . '"';
    }
}
