<?php

// Throw exception if anything fails.
set_error_handler(
	function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);

require_once __DIR__ . '/../../wp-includes/mysql/class-wp-mysql-token.php';
require_once __DIR__ . '/../../wp-includes/mysql/class-wp-mysql-lexer.php';
require_once __DIR__ . '/../../wp-includes/parser/class-wp-parser-grammar.php';
require_once __DIR__ . '/../../wp-includes/parser/class-wp-parser-tree.php';
require_once __DIR__ . '/../../wp-includes/parser/class-wp-parser.php';
require_once __DIR__ . '/../../wp-includes/mysql/class-wp-mysql-parser.php';

function getStats( $total, $failures, $exceptions ) {
	return sprintf(
		'Total: %5d  |  Failures: %4d / %2d%%  |  Exceptions: %4d / %2d%%',
		$total,
		$failures,
		$failures / $total * 100,
		$exceptions,
		$exceptions / $total * 100
	);
}

$grammar_data = include __DIR__ . '/../../wp-includes/mysql/mysql-grammar.php';
$grammar      = new WP_Parser_Grammar( $grammar_data );

$data_dir   = __DIR__ . '/../mysql/data';
$handle     = fopen( "$data_dir/queries.csv", 'r' );
$i          = 1;
$failures   = array();
$exceptions = array();
while ( ( $query = fgetcsv( $handle ) ) !== false ) {
	$query = $query[0];
	if ( null === $query ) {
		continue;
	}

	// Skip overflow queries for now.
	if (
		str_contains( $query, 'func_overflow()' )
		|| str_contains( $query, 'proc_overflow()' )
		|| str_contains( $query, 'table_overflow()' )
		|| str_contains( $query, 'trigger_overflow' )
	) {
		continue;
	}

	try {
		$tokens = WP_MySQL_Lexer::tokenize( $query );
		if ( empty( $tokens ) ) {
			throw new Exception( 'Empty tokens' );
		}

		$parser     = new WP_MySQL_Parser( $grammar, $tokens );
		$parse_tree = $parser->parse();
		if ( null === $parse_tree ) {
			$failures[] = $query;
		}
	} catch ( Exception $e ) {
		$exceptions[] = $query;
	}

	if ( 0 === $i % 1000 ) {
		echo getStats( $i, count( $failures ), count( $exceptions ) ), PHP_EOL;
	}
	++$i;
}

echo getStats( $i, count( $failures ), count( $exceptions ) ), PHP_EOL;

// save stats
file_put_contents(
	"$data_dir/stats.txt",
	getStats( $i, count( $failures ), count( $exceptions ) ) . "\n"
);

// save failures
$file = fopen( "$data_dir/failures.csv", 'w' );
foreach ( $failures as $failure ) {
	fputcsv( $file, array( $failure ) );
}
fclose( $file );

// save exceptions
$file = fopen( "$data_dir/exceptions.csv", 'w' );
foreach ( $exceptions as $exception ) {
	fputcsv( $file, array( $exception ) );
}
fclose( $file );
