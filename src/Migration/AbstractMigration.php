<?php

namespace Phoenix\Migration;

use Phoenix\Database\Adapter\AdapterInterface;
use Phoenix\Exception\DatabaseQueryExecuteException;
use Phoenix\Exception\IncorrectMethodUsageException;
use Phoenix\QueryBuilder\ForeignKey;
use Phoenix\QueryBuilder\Index;
use Phoenix\QueryBuilder\Table;

abstract class AbstractMigration
{
    /** @var Table */
    private $table = null;
    
    /** @var array */
    private $tables = [];
    
    /** @var array list of queries to run */
    private $queries = [];
    
    /** @var AdapterInterface */
    private $adapter;
    
    /** @var boolean wrap queries in up / down to transaction */
    protected $useTransaction = false;  // not working for now :( so I turn it off

    /**
     * @param AdapterInterface $adapter
     */
    final public function __construct(AdapterInterface $adapter)
    {
        $this->adapter = $adapter;
    }
    
    /**
     * @return array
     */
    final public function migrate()
    {
        $this->up();
        return $this->runQueries();
    }
    
    /**
     * @return array
     */
    final public function rollback()
    {
        $this->down();
        return $this->runQueries();
    }
    
    /**
     * need override
     */
    abstract protected function up();

    /**
     * need override
     */
    abstract protected function down();
    
    /**
     * adds sql to list of queries to execute
     * @param string $sql
     */
    final protected function execute($sql)
    {
        $this->queries[] = $sql;
    }

    /**
     * @param string $name
     * @param mixed $primaryColumn
     * true - if you want classic autoincrement integer primary column with name id
     * Column - if you want to define your own column (column is added to list of columns)
     * string - name of column in list of columns
     * array of strings - names of columns in list of columns
     * array of Column - list of own columns (all columns are added to list of columns)
     * other (false, null) - if your table doesn't have primary key
     * @return AbstractMigration
     * @throws IncorrectMethodUsageException
     */
    final protected function table($name, $primaryColumn = true)
    {
        if ($this->table !== null) {
            throw new IncorrectMethodUsageException('Wrong use of method table(). Use one of methods create(), drop() first.');
        }
        $this->table = new Table($name, $primaryColumn);
        return $this;
    }
    
    /**
     * @param string $name name of column
     * @param string $type type of column
     * @param boolean $allowNull default false
     * @param mixed $default default null
     * @param int|null $length length of column, null if you want use default length by column type
     * @param int|null $decimals number of decimals in numeric types (float, double, decimal etc.)
     * @param boolean $signed default true
     * @param boolean $autoincrement default false
     * @return AbstractMigration
     * @throws IncorrectMethodUsageException
     */
    final protected function addColumn(
        $name,
        $type,
        $allowNull = false,
        $default = null,
        $length = null,
        $decimals = null,
        $signed = true,
        $autoincrement = false
    ) {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method addColumn(). Use method table() first.');
        }
        $this->table->addColumn($name, $type, $allowNull, $default, $length, $decimals, $signed, $autoincrement);
        return $this;
    }
    
    /**
     * @param string|array $columns name(s) of column(s)
     * @param string $type type of index (unique, fulltext) default ''
     * @param string $method method of index (btree, hash) default ''
     * @return AbstractMigration
     * @throws IncorrectMethodUsageException
     */
    final protected function addIndex($columns, $type = Index::TYPE_NORMAL, $method = Index::METHOD_DEFAULT)
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method addIndex(). Use method table() first.');
        }
        $this->table->addIndex($columns, $type, $method);
        return $this;
    }
    
    /**
     * @param string|array $columns
     * @param string $referencedTable
     * @param string|array $referencedColumns
     * @param string $onDelete
     * @param string $onUpdate
     * @return AbstractMigration
     * @throws IncorrectMethodUsageException
     */
    final protected function addForeignKey($columns, $referencedTable, $referencedColumns = ['id'], $onDelete = ForeignKey::RESTRICT, $onUpdate = ForeignKey::RESTRICT)
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method addForeignKey(). Use method table() first.');
        }
        $this->table->addForeignKey($columns, $referencedTable, $referencedColumns, $onDelete, $onUpdate);
        return $this;
    }

    /**
     * generate create table query
     * @throws IncorrectMethodUsageException if table() was not called first
     */
    final protected function create()
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method create(). Use method table() first.');
        }
        
        $this->tables[count($this->queries)] = $this->table;
        
        $queryBuilder = $this->adapter->getQueryBuilder();
        $this->queries[] = $queryBuilder->createTable($this->table);
        $this->table = null;
    }
    
    /**
     * generates drop table query
     * @throws IncorrectMethodUsageException if table() was not called first
     */
    final protected function drop()
    {
        if ($this->table === null) {
            throw new IncorrectMethodUsageException('Wrong use of method drop(). Use method table() first.');
        }
        $queryBuilder = $this->adapter->getQueryBuilder();
        $this->queries[] = $queryBuilder->dropTable($this->table);
        $this->table = null;
    }
    
    private function runQueries()
    {
        $results = [];
        $queriesExecuted = 0;
        try {
            if ($this->useTransaction) {
                $this->adapter->startTransaction();
            }
            foreach ($this->queries as $query) {
                $result = $this->adapter->execute($query);
                $queriesExecuted++;
                $results[] = $result;
            }
            $this->queries = [];
            if ($this->useTransaction) {
                $this->adapter->commit();
            }
        } catch (DatabaseQueryExecuteException $e) {
            if ($this->useTransaction) {
                $this->dbRollback($queriesExecuted);
            }
            throw $e;
        }
        
        return $results;
    }
    
    private function dbRollback($queriesExecuted)
    {
        $this->adapter->rollback();
        
        // own rollback for create table
        for ($i = $queriesExecuted - 1; $i >= 0; $i--) {
            if (isset($this->tables[$i])) {
                $queryBuilder = $this->adapter->getQueryBuilder();
                $query = $queryBuilder->dropTable($this->tables[$i]);
                $this->adapter->execute($query);
            }
        }
    }
}