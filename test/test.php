<?php
require_once __DIR__ . '/../package/JSONLex.php';
require_once __DIR__ . '/../package/JSONParser.php';

error_reporting(E_ALL);
$P = new JSONParser();
$L = new JSONLex(fopen(__DIR__ . '/data.json', 'r'));

while ($t = $L->nextToken()) {
	$P->parse($t->type, $t);
}
