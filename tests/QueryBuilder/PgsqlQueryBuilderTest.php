<?php

namespace Phoenix\Tests;

use Phoenix\QueryBuilder\Column;
use Phoenix\QueryBuilder\PgsqlQueryBuilder;
use Phoenix\QueryBuilder\Table;
use PHPUnit_Framework_TestCase;

class PgsqlQueryBuilderTest extends PHPUnit_Framework_TestCase
{
    public function testUnsupportedColumnType()
    {
        $table = new Table('unsupported');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'unsupported'));
        
        $queryCreator = new PgsqlQueryBuilder();
        $this->setExpectedException('\Exception', 'Type "unsupported" is not allowed');
        $queryCreator->createTable($table);
    }

    public function testSimpleCreate()
    {
        $table = new Table('simple');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string'));
        
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE SEQUENCE "simple_seq";',
            'CREATE TABLE "simple" ("id" int4 DEFAULT nextval(\'simple_seq\'::regclass) NOT NULL,"title" varchar(255) NOT NULL,CONSTRAINT "simple_pkey" PRIMARY KEY ("id"));'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testMoreColumns()
    {
        $table = new Table('more_columns');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('alias', 'string', true));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('total', 'integer', false, 0));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('bodytext', 'text', false));
        
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE SEQUENCE "more_columns_seq";',
            'CREATE TABLE "more_columns" ("id" int4 DEFAULT nextval(\'more_columns_seq\'::regclass) NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) DEFAULT NULL,"total" int4 DEFAULT 0 NOT NULL,"bodytext" text NOT NULL,CONSTRAINT "more_columns_pkey" PRIMARY KEY ("id"));',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testNoPrimaryKey()
    {
        $table = new Table('no_primary_key', false);
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', true));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('total', 'integer', false, 0));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('is_deleted', 'boolean', false, false));
        
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "no_primary_key" ("title" varchar(255) DEFAULT NULL,"total" int4 DEFAULT 0 NOT NULL,"is_deleted" bool DEFAULT false NOT NULL);'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testOwnPrimaryKey()
    {
        $table = new Table('own_primary_key', new Column('identifier', 'string', false, null, 32));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "own_primary_key" ("identifier" varchar(32) NOT NULL,"title" varchar(255) DEFAULT \'\' NOT NULL,CONSTRAINT "own_primary_key_pkey" PRIMARY KEY ("identifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testMoreOwnPrimaryKeys()
    {
        $table = new Table('more_own_primary_keys', [new Column('identifier', 'string', false, null, 32), new Column('subidentifier', 'string', false, null, 32)]);
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "more_own_primary_keys" ("identifier" varchar(32) NOT NULL,"subidentifier" varchar(32) NOT NULL,"title" varchar(255) DEFAULT \'\' NOT NULL,CONSTRAINT "more_own_primary_keys_pkey" PRIMARY KEY ("identifier","subidentifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testOneFieldAsPrimaryKey()
    {
        $table = new Table('one_field_as_pk', 'identifier');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('identifier', 'string', false, null, 32));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "one_field_as_pk" ("identifier" varchar(32) NOT NULL,"title" varchar(255) DEFAULT \'\' NOT NULL,CONSTRAINT "one_field_as_pk_pkey" PRIMARY KEY ("identifier"));',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testMoreFieldsAsPrimaryKeys()
    {
        $table = new Table('more_fields_as_pk', ['identifier', 'subidentifier']);
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('identifier', 'string', false, null, 32));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('subidentifier', 'string', false, null, 32));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE TABLE "more_fields_as_pk" ("identifier" varchar(32) NOT NULL,"subidentifier" varchar(32) NOT NULL,"title" varchar(255) DEFAULT \'\' NOT NULL,CONSTRAINT "more_fields_as_pk_pkey" PRIMARY KEY ("identifier","subidentifier"));'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testUnsupportedTypeOfPrimaryKeys()
    {
        $this->setExpectedException('\InvalidArgumentException', 'Unsupported type of primary column');
        $table = new Table('more_fields_as_pk', ['identifier', false]);
    }
    
    public function testUnkownColumnAsPrimaryKey()
    {
        $table = new Table('unknown_primary_key', 'unknown');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('identifier', 'string', false, null, 32));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string', false, ''));
        
        $queryCreator = new PgsqlQueryBuilder();
        $this->setExpectedException('\Exception', 'Column "unknown" not found');
        $queryCreator->createTable($table);
    }
    
    public function testIndexes()
    {
        $table = new Table('table_with_indexes');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('alias', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('bodytext', 'text'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addIndex('sorting', '', 'btree'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addIndex(['title', 'alias'], 'unique'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addIndex('bodytext', 'fulltext', 'hash'));
        
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE SEQUENCE "table_with_indexes_seq";',
            'CREATE TABLE "table_with_indexes" ("id" int4 DEFAULT nextval(\'table_with_indexes_seq\'::regclass) NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) NOT NULL,"sorting" int4 NOT NULL,"bodytext" text NOT NULL,CONSTRAINT "table_with_indexes_pkey" PRIMARY KEY ("id"));',
            'CREATE INDEX "table_with_indexes_sorting" ON "table_with_indexes" USING BTREE ("sorting");',
            'CREATE UNIQUE INDEX "table_with_indexes_title_alias" ON "table_with_indexes" ("title","alias");',
            'CREATE FULLTEXT INDEX "table_with_indexes_bodytext" ON "table_with_indexes" USING HASH ("bodytext");',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testForeignKeys()
    {
        $table = new Table('table_with_foreign_keys');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('alias', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('foreign_table_id', 'integer'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addForeignKey('foreign_table_id', 'second_table'));
        
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE SEQUENCE "table_with_foreign_keys_seq";',
            'CREATE TABLE "table_with_foreign_keys" ("id" int4 DEFAULT nextval(\'table_with_foreign_keys_seq\'::regclass) NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) NOT NULL,"foreign_table_id" int4 NOT NULL,CONSTRAINT "table_with_foreign_keys_pkey" PRIMARY KEY ("id"),CONSTRAINT "table_with_foreign_keys_foreign_table_id" FOREIGN KEY ("foreign_table_id") REFERENCES "second_table" ("id") ON DELETE RESTRICT ON UPDATE RESTRICT);'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testIndexesAndForeignKeys()
    {
        $table = new Table('table_with_indexes_and_foreign_keys');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('alias', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('bodytext', 'text'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('foreign_table_id', 'integer'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addForeignKey('foreign_table_id', 'second_table', 'foreign_id', 'set null', 'set null'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addIndex('sorting', '', 'btree'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addIndex(['title', 'alias'], 'unique'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addIndex('bodytext', 'fulltext', 'hash'));
        
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE SEQUENCE "table_with_indexes_and_foreign_keys_seq";',
            'CREATE TABLE "table_with_indexes_and_foreign_keys" ("id" int4 DEFAULT nextval(\'table_with_indexes_and_foreign_keys_seq\'::regclass) NOT NULL,"title" varchar(255) NOT NULL,"alias" varchar(255) NOT NULL,"sorting" int4 NOT NULL,"bodytext" text NOT NULL,"foreign_table_id" int4 NOT NULL,CONSTRAINT "table_with_indexes_and_foreign_keys_pkey" PRIMARY KEY ("id"),CONSTRAINT "table_with_indexes_and_foreign_keys_foreign_table_id" FOREIGN KEY ("foreign_table_id") REFERENCES "second_table" ("foreign_id") ON DELETE SET NULL ON UPDATE SET NULL);',
            'CREATE INDEX "table_with_indexes_and_foreign_keys_sorting" ON "table_with_indexes_and_foreign_keys" USING BTREE ("sorting");',
            'CREATE UNIQUE INDEX "table_with_indexes_and_foreign_keys_title_alias" ON "table_with_indexes_and_foreign_keys" ("title","alias");',
            'CREATE FULLTEXT INDEX "table_with_indexes_and_foreign_keys_bodytext" ON "table_with_indexes_and_foreign_keys" USING HASH ("bodytext");',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->createTable($table));
    }
    
    public function testDropTable()
    {
        $table = new Table('drop');
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'DROP TABLE "drop"',
            'DROP SEQUENCE IF EXISTS "drop_seq"',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->dropTable($table));
    }
    
    public function testAlterTable()
    {
        // add columns
        $table = new Table('add_columns');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('title', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('alias', 'string'));
        
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE "add_columns" ADD COLUMN "title" varchar(255) NOT NULL,ADD COLUMN "alias" varchar(255) NOT NULL;'
        ];
        $this->assertEquals($expectedQueries, $queryCreator->alterTable($table));
        
        // add index
        $table = new Table('add_index');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addIndex('alias', 'unique'));
        
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'CREATE UNIQUE INDEX "add_index_alias" ON "add_index" ("alias");',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->alterTable($table));
        
        // add column and index
        $table = new Table('add_column_and_index');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('alias', 'string'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addIndex('alias', 'unique'));
        
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE "add_column_and_index" ADD COLUMN "alias" varchar(255) NOT NULL;',
            'CREATE UNIQUE INDEX "add_column_and_index_alias" ON "add_column_and_index" ("alias");',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->alterTable($table));
        
        // add foreign key, index, columns
        $table = new Table('add_columns_index_foreign_key');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('foreign_key_id', 'integer'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addIndex('sorting'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addForeignKey('foreign_key_id', 'referenced_table'));
        
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE "add_columns_index_foreign_key" ADD COLUMN "foreign_key_id" int4 NOT NULL,ADD COLUMN "sorting" int4 NOT NULL;',
            'CREATE INDEX "add_columns_index_foreign_key_sorting" ON "add_columns_index_foreign_key" ("sorting");',
            'ALTER TABLE "add_columns_index_foreign_key" ADD CONSTRAINT "add_columns_index_foreign_key_foreign_key_id" FOREIGN KEY ("foreign_key_id") REFERENCES "referenced_table" ("id") ON DELETE RESTRICT ON UPDATE RESTRICT;',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->alterTable($table));
        
        // remove columns
        
        // remove index
        
        // remove foreign key
        
        // combination of add / remove column, add / remove index, add / remove foreign key
        $table = new Table('all_in_one');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('foreign_key_id', 'integer'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->dropColumn('title'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addIndex('sorting'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->dropIndex('alias'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addForeignKey('foreign_key_id', 'referenced_table'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->dropForeignKey('foreign_key_to_drop_id'));
        
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE "all_in_one" DROP INDEX "alias";',
            'ALTER TABLE "all_in_one" DROP CONSTRAINT "all_in_one_foreign_key_to_drop_id";',
            'ALTER TABLE "all_in_one" DROP COLUMN "title";',
            'ALTER TABLE "all_in_one" ADD COLUMN "foreign_key_id" int4 NOT NULL,ADD COLUMN "sorting" int4 NOT NULL;',
            'CREATE INDEX "all_in_one_sorting" ON "all_in_one" ("sorting");',
            'ALTER TABLE "all_in_one" ADD CONSTRAINT "all_in_one_foreign_key_id" FOREIGN KEY ("foreign_key_id") REFERENCES "referenced_table" ("id") ON DELETE RESTRICT ON UPDATE RESTRICT;',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->alterTable($table));
        
        // mixed order of calls add / remove column, add / remove index, add / remove foreign key - output is the same
        $table = new Table('all_in_one_mixed');
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addIndex('sorting'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->dropForeignKey('foreign_key_to_drop_id'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('foreign_key_id', 'integer'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->dropColumn('title'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addColumn('sorting', 'integer'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->dropIndex('alias'));
        $this->assertInstanceOf('\Phoenix\QueryBuilder\Table', $table->addForeignKey('foreign_key_id', 'referenced_table'));
                
        $queryCreator = new PgsqlQueryBuilder();
        $expectedQueries = [
            'ALTER TABLE "all_in_one_mixed" DROP INDEX "alias";',
            'ALTER TABLE "all_in_one_mixed" DROP CONSTRAINT "all_in_one_mixed_foreign_key_to_drop_id";',
            'ALTER TABLE "all_in_one_mixed" DROP COLUMN "title";',
            'ALTER TABLE "all_in_one_mixed" ADD COLUMN "foreign_key_id" int4 NOT NULL,ADD COLUMN "sorting" int4 NOT NULL;',
            'CREATE INDEX "all_in_one_mixed_sorting" ON "all_in_one_mixed" ("sorting");',
            'ALTER TABLE "all_in_one_mixed" ADD CONSTRAINT "all_in_one_mixed_foreign_key_id" FOREIGN KEY ("foreign_key_id") REFERENCES "referenced_table" ("id") ON DELETE RESTRICT ON UPDATE RESTRICT;',
        ];
        $this->assertEquals($expectedQueries, $queryCreator->alterTable($table));
    }
}
