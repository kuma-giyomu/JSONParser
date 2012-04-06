<?php
require_once __DIR__ . '/../gen/json.lex.php';
require_once __DIR__ . '/../gen/json.php';

error_reporting(E_ALL);
$P = new JSONParser();
$L = new JSONLex(fopen(__DIR__ . '/data.json', 'r'));

while ($t = $L->nextToken()) {
	$P->JSON($t->type, $t);
}
$P->JSON(0);
