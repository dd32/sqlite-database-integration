<?php

// @TODO: Remove the namespace and use statements when replacing the old driver.
namespace WIP;

use PDOStatement;

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
			TABLE_CATALOG TEXT NOT NULL DEFAULT 'def',
			TABLE_SCHEMA TEXT NOT NULL,
			TABLE_NAME TEXT NOT NULL,
			TABLE_TYPE TEXT NOT NULL,
			ENGINE TEXT NOT NULL,
			VERSION INTEGER NOT NULL DEFAULT 10,
			ROW_FORMAT TEXT NOT NULL,
			TABLE_ROWS INTEGER NOT NULL DEFAULT 0,
			AVG_ROW_LENGTH INTEGER NOT NULL DEFAULT 0,
			DATA_LENGTH INTEGER NOT NULL DEFAULT 0,
			MAX_DATA_LENGTH INTEGER NOT NULL DEFAULT 0,
			INDEX_LENGTH INTEGER NOT NULL DEFAULT 0,
			DATA_FREE INTEGER NOT NULL DEFAULT 0,
			AUTO_INCREMENT INTEGER,
			CREATE_TIME TEXT NOT NULL
				DEFAULT CURRENT_TIMESTAMP,
			UPDATE_TIME TEXT,
			CHECK_TIME TEXT,
			TABLE_COLLATION TEXT NOT NULL,
			CHECKSUM INTEGER,
			CREATE_OPTIONS TEXT,
			TABLE_COMMENT TEXT NOT NULL DEFAULT ''
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
	 * @param string $query
	 * @param array $params
	 * @return PDOStatement
	 */
	private function query( string $query, array $params = array() ) {
		return ( $this->query_callback )( $query, $params );
	}
}
