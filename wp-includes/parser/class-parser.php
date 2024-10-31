<?php

require_once __DIR__ . '/class-grammar.php';
require_once __DIR__ . '/class-parse-tree.php';

/*
@TODO:
* ✅ Tokenize MySQL Queries
* ✅ Inline fragments
* ✅ Prune the lookup tree with lookahead table

Possible exploration avenues:
* Memoize token nb/rule matches to avoid repeating work.
* Optimize the grammar to resolve ambiugities
* Generate an expanded PHP parser to optimize matching, right now we're doing a
	whole lot of lookups
*/

class Parser {
	private $tokens;
	private $position;
	private $grammar;

	public function __construct( Grammar $grammar, array $tokens ) {
		$this->grammar  = $grammar;
		$this->tokens   = $tokens;
		$this->position = 0;
	}

	public function parse() {
		$query_rule_id = $this->grammar->get_rule_id( 'query' );
		return $this->parse_recursive( $query_rule_id );
	}


	private function parse_recursive( $rule_id ) {
		//var_dump($this->get_rule_name($rule_id));
		$is_terminal = $rule_id <= $this->grammar->highest_terminal_id;
		if ( $is_terminal ) {
			// Inlining a $this->match($rule_id) call here speeds the
			// parser up by a whooping 10%!
			if ( $this->position >= count( $this->tokens ) ) {
				return null;
			}

			if ( MySQL_Lexer::EMPTY_TOKEN === $rule_id ) {
				return true;
			}

			if ( $this->tokens[ $this->position ]->type === $rule_id ) {
				++$this->position;
				return $this->tokens[ $this->position - 1 ];
			}
			return null;
		}

		$rule = $this->grammar->rules[ $rule_id ];
		if ( ! count( $rule ) ) {
			return null;
		}

		// Bale out from processing the current branch if none of its rules can
		// possibly match the current token.
		if ( isset( $this->grammar->lookahead_is_match_possible[ $rule_id ] ) ) {
			$token_id = $this->tokens[ $this->position ]->type;
			if (
				! isset( $this->grammar->lookahead_is_match_possible[ $rule_id ][ $token_id ] ) &&
				! isset( $this->grammar->lookahead_is_match_possible[ $rule_id ][ MySQL_Lexer::EMPTY_TOKEN ] )
			) {
				return null;
			}
		}

		$rule_name         = $this->grammar->rule_names[ $rule_id ];
		$starting_position = $this->position;
		foreach ( $rule as $branch ) {
			$this->position = $starting_position;
			$node           = new Parse_Tree( $rule_id, $rule_name );
			$branch_matches = true;
			foreach ( $branch as $subrule_id ) {
				$subnode = $this->parse_recursive( $subrule_id );
				if ( null === $subnode ) {
					$branch_matches = false;
					break;
				} elseif ( true === $subnode ) {
					// ε – the rule matched without actually matching a token.
					//     Proceed without adding anything to $match.
					continue;
				} elseif ( is_array( $subnode ) && 0 === count( $subnode ) ) {
					continue;
				}
				if ( is_array( $subnode ) && ! count( $subnode ) ) {
					continue;
				}
				if ( isset( $this->grammar->fragment_ids[ $subrule_id ] ) ) {
					$node->merge_fragment( $subnode );
				} else {
					$node->append_child( $subnode );
				}
			}

			// Negative lookahead for INTO after a valid SELECT statement.
			// If we match a SELECT statement, but there is an INTO keyword after it,
			// we're in the wrong branch and need to leave matching to a later rule.
			// For now, it's hard-coded, but we could extract it to a lookahead table.
			$la = $this->tokens[ $this->position ] ?? null;
			if ( $la && 'selectStatement' === $rule_name && MySQL_Lexer::INTO_SYMBOL === $la->type ) {
				$branch_matches = false;
			}

			if ( true === $branch_matches ) {
				break;
			}
		}

		if ( ! $branch_matches ) {
			$this->position = $starting_position;
			return null;
		}

		if ( 0 === count( $node->children ) ) {
			return true;
		}

		return $node;
	}

	private function get_rule_name( $id ) {
		if ( $id <= $this->grammar->highest_terminal_id ) {
			return MySQL_Lexer::get_token_name( $id );
		}

		return $this->grammar->get_rule_name( $id );
	}
}
