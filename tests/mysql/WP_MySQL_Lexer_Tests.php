<?php

use PHPUnit\Framework\TestCase;

class WP_MySQL_Lexer_Tests extends TestCase {
	/**
	 * Numbers vs. identifiers:
	 *
	 * In MySQL, when an input matches both a number and an identifier, the number always wins.
	 * However, when the number is followed by a non-numeric identifier-like character, it is
	 * considered an identifier... unless it's a float number, which ignores subsequent input.
	 *
	 * @dataProvider data_identifier_or_number
	 */
	public function test_identifier_or_number( $input, $expected ): void {
		$actual = array_map(
			function ( $token ) {
				return $token->get_type();
			},
			WP_MySQL_Lexer::tokenize( $input )
		);

		// Compare token names to get more readable error messages.
		$this->assertSame(
			$this->get_token_names( $expected ),
			$this->get_token_names( $actual )
		);
	}

	public function data_identifier_or_number(): array {
		return array(
			// integer
			array( '123', array( WP_MySQL_Lexer::INT_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( '123abc', array( WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // identifier

			// binary
			array( '0b01', array( WP_MySQL_Lexer::BIN_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( '0b01xyz', array( WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // identifier
			array( "b'01'", array( WP_MySQL_Lexer::BIN_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( "b'01xyz'", array( WP_MySQL_Lexer::BIN_NUMBER, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::INVALID_INPUT, WP_MySQL_Lexer::EOF ) ),

			// hex
			array( '0xab01', array( WP_MySQL_Lexer::HEX_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( '0xab01xyz', array( WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // identifier
			array( "x'ab01'", array( WP_MySQL_Lexer::HEX_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( "x'ab01xyz'", array( WP_MySQL_Lexer::HEX_NUMBER, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::INVALID_INPUT, WP_MySQL_Lexer::EOF ) ),

			// decimal
			array( '123.456', array( WP_MySQL_Lexer::DECIMAL_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( '.123', array( WP_MySQL_Lexer::DECIMAL_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( '123.', array( WP_MySQL_Lexer::DECIMAL_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( '123.456abc', array( WP_MySQL_Lexer::DECIMAL_NUMBER, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // not identifier
			array( '.123abc', array( WP_MySQL_Lexer::DECIMAL_NUMBER, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // not identifier
			array( '123.abc', array( WP_MySQL_Lexer::DECIMAL_NUMBER, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // not identifier

			// float
			array( '1e10', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( '1e+10', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( '1e-10', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( '.1e10', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( '.1e+10', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( '.1e-10', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( '1.1e10', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( '1.1e-10', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( '1.1e+10', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::EOF ) ),
			array( '1e10abc', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // not identifier (this differs from INT/BIN/HEX numbers)
			array( '1e+10abc', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // not identifier
			array( '1e-10abc', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // not identifier
			array( '.1e10abc', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // not identifier
			array( '.1e+10abc', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // not identifier
			array( '.1e-10abc', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // not identifier
			array( '1.1e10abc', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // not identifier
			array( '1.1e+10abc', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // not identifier
			array( '1.1e-10abc', array( WP_MySQL_Lexer::FLOAT_NUMBER, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // not identifier

			// non-numbers
			array( '.SELECT', array( WP_MySQL_Lexer::DOT_SYMBOL, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // not decimal or float
			array( '1+e10', array( WP_MySQL_Lexer::INT_NUMBER, WP_MySQL_Lexer::PLUS_OPERATOR, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // not float
			array( '1-e10', array( WP_MySQL_Lexer::INT_NUMBER, WP_MySQL_Lexer::MINUS_OPERATOR, WP_MySQL_Lexer::IDENTIFIER, WP_MySQL_Lexer::EOF ) ), // not float
		);
	}

	private function get_token_names( array $token_types ): array {
		return array_map(
			function ( $token_type ) {
				return WP_MySQL_Lexer::get_token_name( $token_type );
			},
			$token_types
		);
	}
}
