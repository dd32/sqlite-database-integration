<?php

class Grammar {
	public $rules;
	public $rule_names;
	public $fragment_ids;
	public $lookahead_is_match_possible = array();
	public $lowest_non_terminal_id;
	public $highest_terminal_id;

	public function __construct( array $rules ) {
		$this->inflate( $rules );
	}

	public function get_rule_name( $rule_id ) {
		return $this->rule_names[ $rule_id ];
	}

	public function get_rule_id( $rule_name ) {
		return array_search( $rule_name, $this->rule_names, true );
	}

	/**
	 * Grammar is a packed PHP array to minimize the file size. Every
	 * rule and token is encoded as an integer. It still takes 1.2MB,
	 * maybe we can do better than that with a more efficient encoding,
	 * e.g. what Dennis Snell did for the HTML entity decoder.
	 * Or maybe we can reduce the grammar size by factoring the rules?
	 * Or perhaps we can let go of some parsing rules that SQLite cannot
	 * support anyway?
	 */
	private function inflate( $grammar ) {
		$this->lowest_non_terminal_id = $grammar['rules_offset'];
		$this->highest_terminal_id    = $this->lowest_non_terminal_id - 1;

		foreach ( $grammar['rules_names'] as $rule_index => $rule_name ) {
			$this->rule_names[ $rule_index + $grammar['rules_offset'] ] = $rule_name;
			$this->rules[ $rule_index + $grammar['rules_offset'] ]      = array();
			/**
			 * Treat all intermediate rules as fragments to inline before returning
			 * the final parse tree to the API consumer.
			 *
			 * The original grammar was too difficult to parse with rules like
			 *
			 *    query ::= EOF | ((simpleStatement | beginWork) ((SEMICOLON_SYMBOL EOF?) | EOF))
			 *
			 * We've  factored rules like bitExpr* to separate rules like bitExpr_zero_or_more.
			 * This is super useful for parsing, but it limits the API consumer's ability to
			 * reason about the parse tree.
			 *
			 * The following rules as fragments:
			 *
			 * * Rules starting with a percent sign ("%") – these are intermediate
			 *   rules that are not part of the original grammar. They are useful
			 *
			 */
			if ( '%' === $rule_name[0] ) {
				$this->fragment_ids[ $rule_index + $grammar['rules_offset'] ] = true;
			}
		}

		$this->rules = array();
		foreach ( $grammar['grammar'] as $rule_index => $branches ) {
			$rule_id                 = $rule_index + $grammar['rules_offset'];
			$this->rules[ $rule_id ] = $branches;
		}

		/**
		 * Compute a rule => [token => true] lookup table for each rule
		 * that starts with a terminal OR with another rule that already
		 * has a lookahead mapping.
		 *
		 * This is similar to left-factoring the grammar, even if not quite
		 * the same.
		 *
		 * This enables us to quickly bale out from checking branches that
		 * cannot possibly match the current token. This increased the parser
		 * speed by a whooping 80%!
		 *
		 * The next step could be to:
		 *
		 * * Compute a rule => [token => branch[]] list lookup table and only
		 *   process the branches that have a chance of matching the current token.
		 * * Actually left-factor the grammar as much as possible. This, however,
		 *   could inflate the serialized grammar size.
		 */
		// 5 iterations seem to give us all the speed gains we can get from this.
		for ( $i = 0; $i < 5; $i++ ) {
			foreach ( $grammar['grammar'] as $rule_index => $branches ) {
				$rule_id = $rule_index + $grammar['rules_offset'];
				if ( isset( $this->lookahead_is_match_possible[ $rule_id ] ) ) {
					continue;
				}
				$rule_lookup                                   = array();
				$first_symbol_can_be_expanded_to_all_terminals = true;
				foreach ( $branches as $branch ) {
					$terminals                   = false;
					$branch_starts_with_terminal = $branch[0] < $this->lowest_non_terminal_id;
					if ( $branch_starts_with_terminal ) {
						$terminals = array( $branch[0] );
					} elseif ( isset( $this->lookahead_is_match_possible[ $branch[0] ] ) ) {
						$terminals = array_keys( $this->lookahead_is_match_possible[ $branch[0] ] );
					}

					if ( false === $terminals ) {
						$first_symbol_can_be_expanded_to_all_terminals = false;
						break;
					}
					foreach ( $terminals as $terminal ) {
						$rule_lookup[ $terminal ] = true;
					}
				}
				if ( $first_symbol_can_be_expanded_to_all_terminals ) {
					$this->lookahead_is_match_possible[ $rule_id ] = $rule_lookup;
				}
			}
		}
	}
}
