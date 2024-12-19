<?php

// @TODO: Remove the namespace and use statements when replacing the old driver.
namespace WIP;

use PDOStatement;
use WP_MySQL_Lexer;
use WP_Parser_Node;

class WP_SQLite_Information_Schema_Builder {
	/**
	 * SQL definitions for tables that emulate MySQL "information_schema".
	 *
	 * The full MySQL information schema comprises a large number of tables:
	 *   https://dev.mysql.com/doc/refman/8.4/en/information-schema-table-reference.html
	 *
	 * We only implement a limited subset that is necessary for a database schema
	 * introspection and representation, currently covering the following tables:
	 *
	 *  - TABLES
	 *  - VIEWS
	 *  - COLUMNS
	 *  - STATISTICS (indexes)
	 *  - TABLE_CONSTRAINTS (PK, UNIQUE, FK)
	 *  - CHECK_CONSTRAINTS
	 *  - KEY_COLUMN_USAGE (foreign keys)
	 *  - REFERENTIAL_CONSTRAINTS (foreign keys)
	 *  - TRIGGERS
	 */
	const CREATE_INFORMATION_SCHEMA_QUERIES = array(
		// TABLES
		"CREATE TABLE IF NOT EXISTS _mysql_information_schema_tables (
			TABLE_CATALOG TEXT NOT NULL DEFAULT 'def',  -- always 'def'
			TABLE_SCHEMA TEXT NOT NULL,                 -- database name
			TABLE_NAME TEXT NOT NULL,                   -- table name
			TABLE_TYPE TEXT NOT NULL,                   -- 'BASE TABLE' or 'VIEW'
			ENGINE TEXT NOT NULL,                       -- storage engine
			VERSION INTEGER NOT NULL DEFAULT 10,        -- unused, in MySQL 8 hardcoded to 10
			ROW_FORMAT TEXT NOT NULL,                   -- row storage format @TODO - implement
			TABLE_ROWS INTEGER NOT NULL DEFAULT 0,      -- not implemented
			AVG_ROW_LENGTH INTEGER NOT NULL DEFAULT 0,  -- not implemented
			DATA_LENGTH INTEGER NOT NULL DEFAULT 0,     -- not implemented
			MAX_DATA_LENGTH INTEGER NOT NULL DEFAULT 0, -- not implemented
			INDEX_LENGTH INTEGER NOT NULL DEFAULT 0,    -- not implemented
			DATA_FREE INTEGER NOT NULL DEFAULT 0,       -- not implemented
			AUTO_INCREMENT INTEGER,                     -- not implemented
			CREATE_TIME TEXT NOT NULL                   -- table creation timestamp
				DEFAULT CURRENT_TIMESTAMP,
			UPDATE_TIME TEXT,                           -- table update time
			CHECK_TIME TEXT,                            -- not implemented
			TABLE_COLLATION TEXT NOT NULL,              -- table collation
			CHECKSUM INTEGER,                           -- not implemented
			CREATE_OPTIONS TEXT,                        -- extra CREATE TABLE options
			TABLE_COMMENT TEXT NOT NULL DEFAULT ''      -- comment
		) STRICT",

		// COLUMNS
		"CREATE TABLE IF NOT EXISTS _mysql_information_schema_columns (
			TABLE_CATALOG TEXT NOT NULL DEFAULT 'def',
			TABLE_SCHEMA TEXT NOT NULL,
			TABLE_NAME TEXT NOT NULL,
			COLUMN_NAME TEXT NOT NULL,
			ORDINAL_POSITION INTEGER NOT NULL,
			COLUMN_DEFAULT TEXT,
			IS_NULLABLE TEXT NOT NULL,
			DATA_TYPE TEXT NOT NULL,
			CHARACTER_MAXIMUM_LENGTH INTEGER,
			CHARACTER_OCTET_LENGTH INTEGER,
			NUMERIC_PRECISION INTEGER,
			NUMERIC_SCALE INTEGER,
			DATETIME_PRECISION INTEGER,
			CHARACTER_SET_NAME TEXT,
			COLLATION_NAME TEXT,
			COLUMN_TYPE TEXT NOT NULL,
			COLUMN_KEY TEXT NOT NULL DEFAULT '',
			EXTRA TEXT NOT NULL DEFAULT '',
			PRIVILEGES TEXT NOT NULL,
			COLUMN_COMMENT TEXT NOT NULL DEFAULT '',
			GENERATION_EXPRESSION TEXT NOT NULL DEFAULT '',
			SRS_ID INTEGER
		) STRICT",

		// VIEWS
		// @TODO: Implement.
		'CREATE TABLE IF NOT EXISTS _mysql_information_schema_views (
			TABLE_CATALOG TEXT NOT NULL,
			TABLE_SCHEMA TEXT NOT NULL,
			TABLE_NAME TEXT NOT NULL,
			VIEW_DEFINITION TEXT NOT NULL,
			CHECK_OPTION TEXT NOT NULL,
			IS_UPDATABLE TEXT NOT NULL,
			DEFINER TEXT NOT NULL,
			SECURITY_TYPE TEXT NOT NULL,
			CHARACTER_SET_CLIENT TEXT NOT NULL,
			COLLATION_CONNECTION TEXT NOT NULL,
			ALGORITHM TEXT NOT NULL
		) STRICT',

		// STATISTICS (indexes)
		"CREATE TABLE IF NOT EXISTS _mysql_information_schema_statistics (
			TABLE_CATALOG TEXT NOT NULL DEFAULT 'def',
			TABLE_SCHEMA TEXT NOT NULL,
			TABLE_NAME TEXT NOT NULL,
			NON_UNIQUE INTEGER NOT NULL,
			INDEX_SCHEMA TEXT NOT NULL,
			INDEX_NAME TEXT NOT NULL,
			SEQ_IN_INDEX INTEGER NOT NULL,
			COLUMN_NAME TEXT,
			COLLATION TEXT,
			CARDINALITY INTEGER,
			SUB_PART INTEGER,
			PACKED TEXT,
			NULLABLE TEXT NOT NULL,
			INDEX_TYPE TEXT NOT NULL,
			COMMENT TEXT NOT NULL DEFAULT '',
			INDEX_COMMENT TEXT NOT NULL DEFAULT '',
			IS_VISIBLE TEXT NOT NULL DEFAULT 'YES',
			EXPRESSION TEXT
		) STRICT",

		// TABLE_CONSTRAINTS
		// @TODO: Implement. Could this be just a view?
		'CREATE TABLE IF NOT EXISTS _mysql_information_schema_table_constraints (
			CONSTRAINT_CATALOG TEXT NOT NULL,
			CONSTRAINT_SCHEMA TEXT NOT NULL,
			CONSTRAINT_NAME TEXT NOT NULL,
			TABLE_SCHEMA TEXT NOT NULL,
			TABLE_NAME TEXT NOT NULL,
			CONSTRAINT_TYPE TEXT NOT NULL
		) STRICT',

		// CHECK_CONSTRAINTS
		// @TODO: Implement.
		'CREATE TABLE IF NOT EXISTS _mysql_information_schema_check_constraints (
			CONSTRAINT_CATALOG TEXT NOT NULL,
			CONSTRAINT_SCHEMA TEXT NOT NULL,
			TABLE_NAME TEXT NOT NULL,
			CONSTRAINT_NAME TEXT NOT NULL,
			CHECK_CLAUSE TEXT NOT NULL
		) STRICT',

		// KEY_COLUMN_USAGE
		// @TODO: Implement.
		'CREATE TABLE IF NOT EXISTS _mysql_information_schema_key_column_usage (
			CONSTRAINT_CATALOG TEXT NOT NULL,
			CONSTRAINT_SCHEMA TEXT NOT NULL,
			CONSTRAINT_NAME TEXT NOT NULL,
			TABLE_CATALOG TEXT NOT NULL,
			TABLE_SCHEMA TEXT NOT NULL,
			TABLE_NAME TEXT NOT NULL,
			COLUMN_NAME TEXT NOT NULL,
			ORDINAL_POSITION INTEGER NOT NULL,
			POSITION_IN_UNIQUE_CONSTRAINT INTEGER,
			REFERENCED_TABLE_SCHEMA TEXT,
			REFERENCED_TABLE_NAME TEXT,
			REFERENCED_COLUMN_NAME TEXT
		) STRICT',

		// REFERENTIAL_CONSTRAINTS
		// @TODO: Implement.
		'CREATE TABLE IF NOT EXISTS _mysql_information_schema_referential_constraints (
			CONSTRAINT_CATALOG TEXT NOT NULL,
			CONSTRAINT_SCHEMA TEXT NOT NULL,
			CONSTRAINT_NAME TEXT NOT NULL,
			UNIQUE_CONSTRAINT_CATALOG TEXT NOT NULL,
			UNIQUE_CONSTRAINT_SCHEMA TEXT NOT NULL,
			UNIQUE_CONSTRAINT_NAME TEXT,
			MATCH_OPTION TEXT NOT NULL,
			UPDATE_RULE TEXT NOT NULL,
			DELETE_RULE TEXT NOT NULL,
			REFERENCED_TABLE_NAME TEXT NOT NULL
		) STRICT',

		// TRIGGERS
		// @TODO: Implement.
		'CREATE TABLE IF NOT EXISTS _mysql_information_schema_triggers (
			TRIGGER_CATALOG TEXT NOT NULL,
			TRIGGER_SCHEMA TEXT NOT NULL,
			TRIGGER_NAME TEXT NOT NULL,
			EVENT_MANIPULATION TEXT NOT NULL,
			EVENT_OBJECT_CATALOG TEXT NOT NULL,
			EVENT_OBJECT_SCHEMA TEXT NOT NULL,
			EVENT_OBJECT_TABLE TEXT NOT NULL,
			ACTION_ORDER INTEGER NOT NULL,
			ACTION_CONDITION TEXT,
			ACTION_STATEMENT TEXT NOT NULL,
			ACTION_ORIENTATION TEXT NOT NULL,
			ACTION_TIMING TEXT NOT NULL,
			ACTION_REFERENCE_OLD_TABLE TEXT,
			ACTION_REFERENCE_NEW_TABLE TEXT,
			ACTION_REFERENCE_OLD_ROW TEXT NOT NULL,
			ACTION_REFERENCE_NEW_ROW TEXT NOT NULL,
			CREATED TEXT,
			SQL_MODE TEXT NOT NULL,
			DEFINER TEXT NOT NULL,
			CHARACTER_SET_CLIENT TEXT NOT NULL,
			COLLATION_CONNECTION TEXT NOT NULL,
			DATABASE_COLLATION TEXT NOT NULL
		) STRICT',
	);

	/**
	 * Database name.
	 *
	 * @TODO: Consider passing the database name as a parameter to each method.
	 *        This would expose an API that could support multiple databases
	 *        in the future. Alternatively, it could be a stateful property.
	 *
	 * @var string
	 */
	private $db_name;

	/**
	 * Query callback.
	 *
	 * @TODO: Consider extracting a part of the WP_SQLite_Driver class
	 *        to a class like "WP_SQLite_Connection" and reuse it in both.
	 *
	 * @var callable(string, array): PDOStatement
	 */
	private $query_callback;

	/**
	 * Constructor.
	 *
	 * @param string $db_name
	 * @param callable(string, array): PDOStatement $query_callback
	 */
	public function __construct( string $db_name, callable $query_callback ) {
		$this->db_name        = $db_name;
		$this->query_callback = $query_callback;
	}

	/**
	 * Ensure that the supported information schema tables exist in the SQLite
	 * database. Tables that are missing will be created.
	 */
	public function ensure_information_schema_tables(): void {
		foreach ( self::CREATE_INFORMATION_SCHEMA_QUERIES as $query ) {
			$this->query( $query );
		}
	}

	/**
	 * Analyze CREATE TABLE statement and record data in the information schema.
	 *
	 * @param WP_Parser_Node $node AST node representing a CREATE TABLE statement.
	 */
	public function record_create_table( WP_Parser_Node $node ): void {
		$table_name       = $this->get_value( $node->get_descendant_node( 'tableName' ) );
		$table_engine     = $this->get_table_engine( $node );
		$table_row_format = 'MyISAM' === $table_engine ? 'FIXED' : 'DYNAMIC';
		$table_collation  = $this->get_table_collation( $node );

		// 1. Table.
		$this->insert_values(
			'_mysql_information_schema_tables',
			array(
				'table_schema'    => $this->db_name,
				'table_name'      => $table_name,
				'table_type'      => 'BASE TABLE',
				'engine'          => $table_engine,
				'row_format'      => $table_row_format,
				'table_collation' => $table_collation,
			)
		);
	}

	private function get_table_engine( WP_Parser_Node $node ): string {
		$engine_node = $node->get_descendant_node( 'engineRef' );
		if ( null === $engine_node ) {
			return 'InnoDB';
		}

		$engine = strtoupper( $this->get_value( $engine_node ) );
		if ( 'INNODB' === $engine ) {
			return 'InnoDB';
		} elseif ( 'MYISAM' === $engine ) {
			return 'MyISAM';
		}
		return $engine;
	}

	private function get_table_collation( WP_Parser_Node $node ): string {
		$collate_node = $node->get_descendant_node( 'collationName' );
		if ( null === $collate_node ) {
			// @TODO: Use default DB collation or DB_CHARSET & DB_COLLATE.
			return 'utf8mb4_general_ci';
		}
		return strtolower( $this->get_value( $collate_node ) );
	}

	/**
	 * This is a helper function to get the full unescaped value of a node.
	 *
	 * @TODO: This should be done in a more correct way, for names maybe allowing
	 *        descending only a single-child hierarchy, such as these:
	 *          identifier -> pureIdentifier -> IDENTIFIER
	 *          identifier -> pureIdentifier -> BACKTICK_QUOTED_ID
	 *          identifier -> pureIdentifier -> DOUBLE_QUOTED_TEXT
	 *          etc.
	 *
	 *        For saving "DEFAULT ..." in column definitions, we actually need to
	 *        serialize the whole node, in the case of expressions. This may mean
	 *        implementing an MySQL AST -> string printer.
	 *
	 * @param WP_Parser_Node $node
	 * @return string
	 */
	private function get_value( WP_Parser_Node $node ): string {
		$full_value = '';
		foreach ( $node->get_children() as $child ) {
			if ( $child instanceof WP_Parser_Node ) {
				$value = $this->get_value( $child );
			} elseif ( WP_MySQL_Lexer::BACK_TICK_QUOTED_ID === $child->id ) {
				$value = substr( $child->value, 1, -1 );
				$value = str_replace( '\`', '`', $value );
				$value = str_replace( '``', '`', $value );
			} elseif ( WP_MySQL_Lexer::SINGLE_QUOTED_TEXT === $child->id ) {
				$value = $child->value;
				$value = substr( $value, 1, -1 );
				$value = str_replace( '\"', '"', $value );
				$value = str_replace( '""', '"', $value );
			} elseif ( WP_MySQL_Lexer::DOUBLE_QUOTED_TEXT === $child->id ) {
				$value = $child->value;
				$value = substr( $value, 1, -1 );
				$value = str_replace( '\"', '"', $value );
				$value = str_replace( '""', '"', $value );
			} else {
				$value = $child->value;
			}
			$full_value .= $value;
		}
		return $full_value;
	}

	private function insert_values( string $table_name, array $data ): void {
		$this->query(
			'
				INSERT INTO ' . $table_name . ' (' . implode( ', ', array_keys( $data ) ) . ')
				VALUES (' . implode( ', ', array_fill( 0, count( $data ), '?' ) ) . ')
			',
			array_values( $data )
		);
	}

	/**
	 * @param string $query
	 * @param array $params
	 * @return PDOStatement
	 */
	private function query( string $query, array $params = array() ) {
		return ( $this->query_callback )( $query, $params );
	}
}
