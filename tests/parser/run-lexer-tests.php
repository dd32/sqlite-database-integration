<?php

// Throw exception if anything fails.
set_error_handler(
	function ( $severity, $message, $file, $line ) {
		throw new ErrorException( $message, 0, $severity, $file, $line );
	}
);

require_once __DIR__ . '/../../wp-includes/mysql/class-wp-mysql-token.php';
require_once __DIR__ . '/../../wp-includes/mysql/class-wp-mysql-lexer.php';

$handle = fopen( __DIR__ . '/data/queries.csv', 'r' );

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

// Add some manual tests
$tests = array(
	/**
	 * Numbers vs. identifiers:
	 *
	 * In MySQL, when an input matches both a number and an identifier, the number always wins.
	 * However, when the number is followed by a non-numeric identifier-like character, it is
	 * considered an identifier... unless it's a float number, which ignores subsequent input.
	 */

	// INT numbers vs. identifiers
	'123'        => array( 'INT_NUMBER', 'EOF' ),
	'123abc'     => array( 'IDENTIFIER', 'EOF' ), // identifier

	// BIN numbers vs. identifiers
	'0b01'       => array( 'BIN_NUMBER', 'EOF' ),
	'0b01xyz'    => array( 'IDENTIFIER', 'EOF' ), // identifier
	"b'01'"      => array( 'BIN_NUMBER', 'EOF' ),
	"b'01xyz'"   => array( 'BIN_NUMBER', 'IDENTIFIER', 'INVALID_INPUT', 'EOF' ),

	// HEX numbers vs. identifiers
	'0xab01'     => array( 'HEX_NUMBER', 'EOF' ),
	'0xab01xyz'  => array( 'IDENTIFIER', 'EOF' ), // identifier
	"x'ab01'"    => array( 'HEX_NUMBER', 'EOF' ),
	"x'ab01xyz'" => array( 'HEX_NUMBER', 'IDENTIFIER', 'INVALID_INPUT', 'EOF' ),

	// DECIMAL numbers vs. identifiers
	'123.456'    => array( 'DECIMAL_NUMBER', 'EOF' ),
	'.123'       => array( 'DECIMAL_NUMBER', 'EOF' ),
	'123.'       => array( 'DECIMAL_NUMBER', 'EOF' ),
	'123.456abc' => array( 'DECIMAL_NUMBER', 'IDENTIFIER', 'EOF' ), // not identifier
	'.123abc'    => array( 'DECIMAL_NUMBER', 'IDENTIFIER', 'EOF' ), // not identifier
	'123.abc'    => array( 'DECIMAL_NUMBER', 'IDENTIFIER', 'EOF' ), // not identifier

	// FLOAT numbers vs. identifiers
	'1e10'       => array( 'FLOAT_NUMBER', 'EOF' ),
	'1e+10'      => array( 'FLOAT_NUMBER', 'EOF' ),
	'1e-10'      => array( 'FLOAT_NUMBER', 'EOF' ),
	'.1e10'      => array( 'FLOAT_NUMBER', 'EOF' ),
	'.1e+10'     => array( 'FLOAT_NUMBER', 'EOF' ),
	'.1e-10'     => array( 'FLOAT_NUMBER', 'EOF' ),
	'1.1e10'     => array( 'FLOAT_NUMBER', 'EOF' ),
	'1.1e-10'    => array( 'FLOAT_NUMBER', 'EOF' ),
	'1.1e+10'    => array( 'FLOAT_NUMBER', 'EOF' ),
	'1e10abc'    => array( 'FLOAT_NUMBER', 'IDENTIFIER', 'EOF' ), // not identifier (this differs from INT/BIN/HEX numbers)
	'1e+10abc'   => array( 'FLOAT_NUMBER', 'IDENTIFIER', 'EOF' ), // not identifier
	'1e-10abc'   => array( 'FLOAT_NUMBER', 'IDENTIFIER', 'EOF' ), // not identifier
	'.1e10abc'   => array( 'FLOAT_NUMBER', 'IDENTIFIER', 'EOF' ), // not identifier
	'.1e+10abc'  => array( 'FLOAT_NUMBER', 'IDENTIFIER', 'EOF' ), // not identifier
	'.1e-10abc'  => array( 'FLOAT_NUMBER', 'IDENTIFIER', 'EOF' ), // not identifier
	'1.1e10abc'  => array( 'FLOAT_NUMBER', 'IDENTIFIER', 'EOF' ), // not identifier
	'1.1e+10abc' => array( 'FLOAT_NUMBER', 'IDENTIFIER', 'EOF' ), // not identifier
	'1.1e-10abc' => array( 'FLOAT_NUMBER', 'IDENTIFIER', 'EOF' ), // not identifier

	// Non-numbers
	'.SELECT'    => array( 'DOT_SYMBOL', 'IDENTIFIER', 'EOF' ), // not decimal or float
	'1+e10'      => array( 'INT_NUMBER', 'PLUS_OPERATOR', 'IDENTIFIER', 'EOF' ), // not float
	'1-e10'      => array( 'INT_NUMBER', 'MINUS_OPERATOR', 'IDENTIFIER', 'EOF' ), // not float
);

$failures = 0;
foreach ( $tests as $input => $expected ) {
	$tokens      = WP_MySQL_Lexer::tokenize( $input );
	$token_names = array_map(
		function ( $token ) {
			return $token->get_name();
		},
		$tokens
	);
	if ( $token_names !== $expected ) {
		$failures += 1;
		echo "\nFailed test for input: $input\n";
		echo '  Expected: ', implode( ', ', $expected ), "\n";
		echo '  Actual:   ', implode( ', ', $token_names ), "\n";
	}
}
if ( $failures > 0 ) {
	echo "\n$failures tests failed!\n";
}
