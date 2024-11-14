<?php

require_once __DIR__ . '/../../wp-includes/parser/class-wp-parser.php';
require_once __DIR__ . '/../../wp-includes/parser/class-wp-parser-grammar.php';
require_once __DIR__ . '/../../wp-includes/parser/class-wp-parser-node.php';
require_once __DIR__ . '/../../wp-includes/mysql/class-wp-mysql-lexer.php';
require_once __DIR__ . '/../../wp-includes/mysql/class-wp-mysql-token.php';
require_once __DIR__ . '/../../wp-includes/mysql/class-wp-mysql-parser.php';
require_once __DIR__ . '/../../wp-includes/sqlite-ast/class-wp-sqlite-expression.php';
require_once __DIR__ . '/../../wp-includes/sqlite-ast/class-wp-sqlite-driver.php';
require_once __DIR__ . '/../../wp-includes/sqlite-ast/class-wp-sqlite-token-factory.php';
require_once __DIR__ . '/../../wp-includes/sqlite-ast/class-wp-sqlite-token.php';
require_once __DIR__ . '/../../wp-includes/sqlite-ast/class-wp-sqlite-query-builder.php';

use WIP\WP_SQLite_Driver;

$driver = new WP_SQLite_Driver( new PDO( 'sqlite::memory:' ) );

$query = "SELECT * FROM t1 LEFT JOIN t2 ON t1.id = t2.t1_id WHERE t1.name = 'abc'";

echo $driver->query( $query );
