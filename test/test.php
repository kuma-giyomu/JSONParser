<?php
require_once __DIR__ . '/../package/JSONLex.php';
require_once __DIR__ . '/../package/JSONGrammar.php';

error_reporting(E_ALL);
$P = new JSONGrammarParser();
$L = new JSONLex(fopen(__DIR__ . '/data.json', 'r'));

while ($t = $L->nextToken()) {
	$P->JSONGrammar($t->type, $t);
}
$P->JSONGrammar(0);
