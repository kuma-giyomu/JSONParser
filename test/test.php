<?php
require_once dirname(__DIR__) . DIRECTORY_SEPARATOR . 'package/JSONParser.php';

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

// initialise the parser object
$parser = new JSONParser();

// sets the callbacks
$parser->setArrayHandlers('arrayStart', 'arrayEnd');
$parser->setObjectHandlers('objStart', 'objEnd');
$parser->setPropertyHandler('property');
$parser->setScalarHandler('scalar');

echo "Parsing top level object document...\n";
// parse the document
$parser->parseDocument(__DIR__ . '/data.json');

$parser->initialise();

echo "Parsing top level array document...\n";
// parse the top level array
$parser->parseDocument(__DIR__ . '/array.json');
