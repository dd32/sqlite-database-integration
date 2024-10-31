<?php

class MySQL_Token {
	public $type;
	public $text;
	public $channel;

	public function __construct( $type, $text, $channel = null ) {
		$this->type    = $type;
		$this->text    = $text;
		$this->channel = $channel;
	}

	public function get_type() {
		return $this->type;
	}

	public function get_name() {
		return MySQL_Lexer::get_token_name( $this->type );
	}

	public function get_text() {
		return $this->text;
	}

	public function get_channel() {
		return $this->channel;
	}

	public function __toString() {
		return $this->text . '<' . $this->type . ',' . $this->get_name() . '>';
	}

	public function extract_value() {
		if ( MySQL_Lexer::BACK_TICK_QUOTED_ID === $this->type ) {
			return substr( $this->text, 1, -1 );
		} elseif ( MySQL_Lexer::DOUBLE_QUOTED_TEXT === $this->type ) {
			return substr( $this->text, 1, -1 );
		} elseif ( MySQL_Lexer::SINGLE_QUOTED_TEXT === $this->type ) {
			return substr( $this->text, 1, -1 );
		} else {
			return $this->text;
		}
	}
}
