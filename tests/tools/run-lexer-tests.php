<?php

// Throw exception if anything fails.
set_error_handler(
	function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);

require_once __DIR__ . '/../../wp-includes/mysql/class-wp-mysql-token.php';
require_once __DIR__ . '/../../wp-includes/mysql/class-wp-mysql-lexer.php';

$handle = fopen( __DIR__ . '/../mysql/data/queries.csv', 'r' );

$i     = 1;
$start = microtime( true );
while ( ( $query = fgetcsv( $handle ) ) !== false ) {
	$query = $query[0];

	$tokens = WP_MySQL_Lexer::tokenize( $query );
	if ( empty( $tokens ) ) {
		throw new Exception( 'Failed to tokenize query: ' . $query );
	}
	++$i;
}

echo "Tokenized $i queries in ", microtime( true ) - $start, 's', PHP_EOL;
