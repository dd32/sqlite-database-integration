<?php

// Throw exception if anything fails.
set_error_handler(
	function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);

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

require_once __DIR__ . '/../../custom-parser/parser/DynamicRecursiveDescentParser.php';
require_once __DIR__ . '/../../custom-parser/parser/MySQLLexer.php';

$grammar_data = include __DIR__ . '/../../custom-parser/parser/grammar.php';
$grammar      = new Grammar( $grammar_data );

$handle     = fopen( __DIR__ . '/data/queries.csv', 'r' );
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
		$tokens = tokenize_query( $query );
		if ( empty( $tokens ) ) {
			throw new Exception( 'Empty tokens' );
		}

		$parser     = new DynamicRecursiveDescentParser( $grammar, $tokens );
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
	__DIR__ . '/data/stats.txt',
	getStats( $i, count( $failures ), count( $exceptions ) ) . "\n"
);

// save failures
$file = fopen( __DIR__ . '/data/failures.csv', 'w' );
foreach ( $failures as $failure ) {
	fputcsv( $file, array( $failure ) );
}
fclose( $file );

// save exceptions
$file = fopen( __DIR__ . '/data/exceptions.csv', 'w' );
foreach ( $exceptions as $exception ) {
	fputcsv( $file, array( $exception ) );
}
fclose( $file );
