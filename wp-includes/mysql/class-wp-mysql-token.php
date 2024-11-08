<?php

class WP_MySQL_Token {
	public $type;
	public $text;

	public function __construct( $type, $text ) {
		$this->type = $type;
		$this->text = $text;
	}

	public function get_type() {
		return $this->type;
	}

	public function get_name() {
		return WP_MySQL_Lexer::get_token_name( $this->type );
	}

	public function get_text() {
		return $this->text;
	}

	public function __toString() {
		return $this->text . '<' . $this->type . ',' . $this->get_name() . '>';
	}

	public function extract_value() {
		if ( WP_MySQL_Lexer::BACK_TICK_QUOTED_ID === $this->type ) {
			return substr( $this->text, 1, -1 );
		} elseif ( WP_MySQL_Lexer::DOUBLE_QUOTED_TEXT === $this->type ) {
			return substr( $this->text, 1, -1 );
		} elseif ( WP_MySQL_Lexer::SINGLE_QUOTED_TEXT === $this->type ) {
			return substr( $this->text, 1, -1 );
		} else {
			return $this->text;
		}
	}
}
