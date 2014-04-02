<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'JLexBase.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'JSONParser.php';

%%

%{
	private $buffer = '';
%}

%function nextToken
%char
%line
%unicode
%state STRING_BEGIN
%class JSONLex

HEX_D = [a-fA-F0-9]
INT = [-]?[0-9]+
DOUBLE = {INT}((\.[0-9]+)?([eE][-+]?[0-9]+)?)
WS = [ \t\r\n]
UNICODE_SEQUENCE = \\(u|U){HEX_D}{HEX_D}{HEX_D}{HEX_D}
UNESCAPED_CH = [^\"\\]

%%

<STRING_BEGIN> \"								{ $this->yybegin(self::YYINITIAL); return $this->createToken(JSONParser::TK_STRING, $this->buffer); }
<STRING_BEGIN> {UNICODE_SEQUENCE}				{ $this->buffer .= json_decode('"' . $this->yytext() . '"'); }
<STRING_BEGIN> {UNESCAPED_CH}+					{ $this->buffer .= $this->yytext(); }
<STRING_BEGIN> \\\"								{ $this->buffer .= '"'; }
<STRING_BEGIN> \\\\								{ $this->buffer .= '\\'; }
<STRING_BEGIN> "/"								{ $this->buffer .= "/"; }
<STRING_BEGIN> "\/"								{ $this->buffer .= "/"; }
<STRING_BEGIN> "\b"								{ $this->buffer .= "\b"; }
<STRING_BEGIN> "\f"								{ $this->buffer .= "\f"; }
<STRING_BEGIN> "\n"								{ $this->buffer .= "\n"; }
<STRING_BEGIN> "\r"								{ $this->buffer .= "\r"; }
<STRING_BEGIN> "\t"								{ $this->buffer .= "\t"; }
                                                                                                
<YYINITIAL> \"									{ $this->buffer = ''; $this->yybegin(self::STRING_BEGIN); }
<YYINITIAL> {INT}                               { return $this->createToken(JSONParser::TK_NUMBER); }
<YYINITIAL> {DOUBLE}                    		{ return $this->createToken(JSONParser::TK_NUMBER); }
<YYINITIAL> true|false	              			{ return $this->createToken(JSONParser::TK_BOOL); }
<YYINITIAL> null                              	{ return $this->createToken(JSONParser::TK_NULL); }
<YYINITIAL> "{"                                 { return $this->createToken(JSONParser::TK_LEFT_BRACE); }
<YYINITIAL> "}"                                 { return $this->createToken(JSONParser::TK_RIGHT_BRACE); }
<YYINITIAL> "["                                 { return $this->createToken(JSONParser::TK_LEFT_SQUARE); }
<YYINITIAL> "]"                                 { return $this->createToken(JSONParser::TK_RIGHT_SQUARE); }
<YYINITIAL> ","                                 { return $this->createToken(JSONParser::TK_COMMA); }
<YYINITIAL> ":"                                 { return $this->createToken(JSONParser::TK_COLON); }
<YYINITIAL> {WS}+                       		{}
