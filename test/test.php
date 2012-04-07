<?php
define('PROJECT_ROOT', dirname(__DIR__) . DIRECTORY_SEPARATOR);
require_once PROJECT_ROOT . 'package/JSONLex.php';
require_once PROJECT_ROOT . 'package/JSONParser.php';

error_reporting(E_ALL);

function objStart($value, $property) {
	printf("{\n");
}

function objEnd($value, $property) {
	printf("}\n");
}

function arrayStart($value, $property) {
	printf("[\n");
}

function arrayEnd($value, $property) {
	printf("]\n");
}

function property($value, $property) {
	printf("Property: %s\n", $value);
}

function scalar($value, $property) {
	printf("Value: %s\n", $value);
}

$lexer = new JSONLex(fopen(__DIR__ . '/data.json', 'r'));

$parser = new JSONParser();
$parser->setArrayHandlers('arrayStart', 'arrayEnd');
$parser->setObjectHandlers('objStart', 'objEnd');
$parser->setPropertyHandler('property');
$parser->setScalarHandler('scalar');

while ($token = $lexer->nextToken()) {
	$parser->parse($token->type, $token);
}
