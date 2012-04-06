/* This is a lemon grammar for the JSON format */
%name JSONGrammar
%token_prefix TK_

/* this defines a symbol for the lexer */
%nonassoc PRAGMA.

start ::= object.

object ::= LEFT_BRACE RIGHT_BRACE.
object ::= LEFT_BRACE members RIGHT_BRACE.

members ::= pair.
members ::= pair COMMA members.

pair ::= string COLON value.

string ::= STRING_VALUE.

value ::= object.
value ::= array.
value ::= string.
value ::= VALUE.

array ::= LEFT_SQUARE RIGHT_SQUARE.
array ::= LEFT_SQUARE elements RIGHT_SQUARE.

elements ::= value.
elements ::= value COMMA elements.