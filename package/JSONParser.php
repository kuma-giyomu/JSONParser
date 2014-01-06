<?php
require_once __DIR__ . DIRECTORY_SEPARATOR . 'JSONLex.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'JSONParserException.php';

/**
 * This code is released under the MIT license.
 * For more details, see the accompanying LICENCE file in this folder.
 * 
 * Main parser class.
 * 
 * This class uses the tokens fed by the Lexer to check the grammar and 
 * fire event handlers when a token has been successfully identified and validated.
 * A JSONParserException will be fired if the syntax does not match the JSON specs.
 * 
 * @author Guillaume Bodi
 *
 */
class JSONParser {
	
	/**
	 * Initial token, bootstrap point for the parser.
	 * 
	 * @var number
	 */
	const TK_INIT = 0;
	
	/**
	 * String token code.
	 * 
	 * @var number
	 */
	const TK_STRING = 100;
	
	/**
	 * Property token code.
	 * Note: this token is NEVER provided by the lexer (since the lexer is context free).
	 * This token is only assigned by the parser for internal logic.
	 * 
	 * @var number
	 */
	const TK_PROPERTY = 101;
	
	/**
	 * Number token code. Covers integer, floats.
	 * 
	 * @var number
	 */
	const TK_NUMBER = 2;
	
	/**
	 * Boolean token code. Classical <code>true</code> or <code>false</code>
	 * 
	 * @var number
	 */
	const TK_BOOL = 3;
	
	/**
	 * Null token code.
	 * 
	 * @var number
	 */
	const TK_NULL = 4;
	
	/**
	 * Left curly brace token code.
	 * 
	 * @var number
	 */
	const TK_LEFT_BRACE = 5;
	
	/**
	 * Right curly brace token code.
	 * 
	 * @var number
	 */
	const TK_RIGHT_BRACE = 6;
	
	/**
	 * Left square brace token code.
	 * 
	 * @var number
	 */
	const TK_LEFT_SQUARE = 7;
	
	/**
	 * Right square brace token code.
	 * 
	 * @var number
	 */
	const TK_RIGHT_SQUARE = 8;
	
	/**
	 * Comma token code.
	 * 
	 * @var number
	 */
	const TK_COMMA = 9;
	
	/**
	 * Colon token code.
	 * 
	 * @var number
	 */
	const TK_COLON = 10;
	
	/**
	 * Initial parser state. Used by the parser to detect which token should be expected next.
	 * 
	 * @var number
	 */
	const STATE_INIT = 0;
	
	/**
	 * Marks that the parser is currently parsing an object. Affects the next token validation.
	 * 
	 * @var number
	 */
	const STATE_OBJECT = 1;
	
	/**
	 * Marks that the parser is currently parsing an array. Affects the next token validation.
	 * 
	 * @var number
	 */
	const STATE_ARRAY = 2;
	
	/**
	 * Next token validation table.
	 * 
	 * Depending on the parser state and the current token, lists all acceptable values for the next token.
	 * 
	 * @var unknown_type
	 */
	static $ALLOWED_NEXT_TOKENS = array(
		self::STATE_INIT => array(
			self::TK_INIT => array(self::TK_LEFT_BRACE, self::TK_LEFT_SQUARE)
		),
	
		self::STATE_OBJECT => array(
			self::TK_LEFT_BRACE => array(self::TK_STRING, self::TK_RIGHT_BRACE),
			self::TK_COMMA => array(self::TK_STRING),
			self::TK_COLON => array(self::TK_STRING, self::TK_NUMBER, self::TK_NULL, self::TK_BOOL, self::TK_LEFT_SQUARE, self::TK_LEFT_BRACE),
			self::TK_STRING => array(self::TK_COMMA, self::TK_RIGHT_BRACE),
			self::TK_NUMBER => array(self::TK_COMMA, self::TK_RIGHT_BRACE),
			self::TK_NULL => array(self::TK_COMMA, self::TK_RIGHT_BRACE),
			self::TK_BOOL => array(self::TK_COMMA, self::TK_RIGHT_BRACE),
			self::TK_RIGHT_SQUARE => array(self::TK_COMMA, self::TK_RIGHT_BRACE),
			self::TK_RIGHT_BRACE => array(self::TK_COMMA, self::TK_RIGHT_BRACE),
			
			//TK_PROPERTY is never sent by the lexer but is a contextual token set by the parser
			self::TK_PROPERTY => array(self::TK_COLON),
		),
		
		self::STATE_ARRAY => array(
			self::TK_LEFT_SQUARE => array(self::TK_STRING, self::TK_NUMBER, self::TK_NULL, self::TK_BOOL, self::TK_LEFT_SQUARE, self::TK_LEFT_BRACE, self::TK_RIGHT_SQUARE),
			self::TK_COMMA => array(self::TK_STRING, self::TK_NUMBER, self::TK_NULL, self::TK_BOOL, self::TK_LEFT_SQUARE, self::TK_LEFT_BRACE),
			self::TK_STRING => array(self::TK_COMMA, self::TK_RIGHT_SQUARE),
			self::TK_NUMBER => array(self::TK_COMMA, self::TK_RIGHT_SQUARE),
			self::TK_NULL => array(self::TK_COMMA, self::TK_RIGHT_SQUARE),
			self::TK_BOOL => array(self::TK_COMMA, self::TK_RIGHT_SQUARE),
			self::TK_RIGHT_SQUARE => array(self::TK_COMMA, self::TK_RIGHT_SQUARE),
			self::TK_RIGHT_BRACE => array(self::TK_COMMA, self::TK_RIGHT_SQUARE),
		)
	);
	
	/**
	 * Tracks the currently active token code.
	 * 
	 * @var number
	 */
	private $currentTokenId;
	
	/**
	 * Tracks the stack of states the parser has traversed so far.
	 * The most recent (= active) state will be the first element of the array.
	 * 
	 * @var array
	 */
	private $stateStack = array();
	
	/**
	 * Currently active property.
	 * This member is set to the value of the last property token parsed.
	 * This member is set to null when no property can be associated with 
	 * a token (eg. first object or values of an array).
	 * 
	 * @var JLexToken
	 */
	private $activeProperty = null;
	
	/**
	 * Callable fired if set to a non null value when encountering a new object.
	 * The callback function expects two arguments($value, $property).
	 * 
	 * @var callable
	 */
	private $objectStartHandler;
	
	/**
	 * Callable fired if set to a non null value when closing an object.
	 * The callback function expects two arguments($value, $property).
	 * 
	 * @var callable
	 */
	private $objectEndHandler;
	
	/**
	 * Callable fired if set to a non null value when encountering an array.
	 * The callback function expects two arguments($value, $property).
	 * 
	 * @var callable
	 */
	private $arrayStartHandler;
	
	/**
	 * Callable fired if set to a non null value when closing an array.
	 * The callback function expects two arguments($value, $property).
	 * 
	 * @var callable
	 */
	private $arrayEndHandler;
	
	/**
	 * Callable fired if set to a non null value when encountering a property.
	 * The callback function expects two arguments($value, $property).
	 * 
	 * Note: the second argument property will always be null, but the form is kept for consistency.
	 * 
	 * @var callable
	 */
	private $propertyHandler;
	
	/**
	 * Callable fired if set to a non null value when encountering a scalar value (boolean, null, number, string).
	 * The callback function expects two arguments($value, $property).
	 * 
	 * @var callable
	 */
	private $scalarHandler;
	
	/**
	 * Base constructore for the parser. Ensures that the parser is initialised.
	 */
	public function __construct() {
		$this->initialise();
	}
	
	/**
	 * Resets the parser. 
	 * It is necessary to call initialise again if you plan to reuse the same parser instance with a different document.
	 */
	public function initialise() {
		$this->currentTokenId = self::TK_INIT;
		$this->stateStack = array(self::STATE_INIT);
		$this->activeProperty = null;
	}
	
	/**
	 * Accept a new token, generally provided by a Lexer.
	 * 
	 * @param integer $tokenId
	 * @param JLexToken $token
	 * @throws JSONParserException if the document does not have a valid structure. 
	 */
	public function parse($tokenId, $token) {
		if(!$this->validateToken($tokenId)) {
			throw new JSONParserException('Invalid syntax: expected another token');
		}
		
		$this->processCurrentToken($tokenId, $token);
	}
	
	/**
	 * Convenience method for preparing a lexer and parsing a specific file.
	 * @param resource|string $file resource to read
	 * @throws JSONParserException if the resource to read cannot be found 
	 * OR if the document does not conform to JSON's syntax.
	 */
	public function parseDocument($file) {
		// check the parameter type to see if we need to open a new stream
		$fileResource = is_resource($file) ? $file : fopen($file, 'r');
		if(is_null($fileResource)) {
			throw new JSONParserException(sprintf('Could not open resource %s in reading', $file));
		}
		
		// instanciate a lexer
		$lexer = new JSONLex($fileResource);
		
		// parse the document
		while ($token = $lexer->nextToken()) {
			$this->parse($token->type, $token);
		}
	}
	
	/**
	 * 
	 * 
	 * @param integer $tokenId
	 * @param JLexToken $token
	 */
	private function processCurrentToken($tokenId, $token) {
		// update the current token
		$this->currentTokenId = $tokenId;
		
		// callback function that needs to be fired
		$handler = null;
		
		// saves a reference to the currently active property for the callback
		$relatedProperty = $this->activeProperty;
		
		// special parser updates according to the new token
		switch($this->currentTokenId) {
			case self::TK_COLON:
				// do nothing
				break;
			
			case self::TK_COMMA;
				// marks the end of a property definition in an object context
				// array values do not have properties anyway.
				$this->activeProperty = null;
				break;
			
			case self::TK_LEFT_BRACE;
				// starts a new object
				$this->activeProperty = null;
				
				// update the parser state
				array_unshift($this->stateStack, self::STATE_OBJECT);
				
				// sets the handler
				$handler = $this->objectStartHandler;
				break;
				
			case self::TK_RIGHT_BRACE;
				// update the parser state
				array_shift($this->stateStack);
				
				// sets the handler
				$handler = $this->objectEndHandler;
				break;
				
			case self::TK_LEFT_SQUARE;
				// starts a new array
				$this->activeProperty = null;
				
				// update the parser state
				array_unshift($this->stateStack, self::STATE_ARRAY);
				
				// sets the handler
				$handler = $this->arrayStartHandler;
				break;
				
			case self::TK_RIGHT_SQUARE;
				// closes an existing array
			
				// update the parser state
				array_shift($this->stateStack);
				
				// sets the handler
				$handler = $this->arrayEndHandler;
				break;
				
			case self::TK_STRING;
				// a string has been detected
				// checks if a property has already been set
				if($this->getCurrentState() == self::STATE_OBJECT && is_null($this->activeProperty)) {
					// no property set, so we assume this is a property
					$this->activeProperty = $token;
					
					// a property has no associated property
					$relatedProperty = null;
					
					// update the parser token 
					$this->currentTokenId = self::TK_PROPERTY;
					
					// sets the handler
					$handler = $this->propertyHandler;
				} else {
					// sets the handler
					$handler = $this->scalarHandler;
				}
				break;
				
			case self::TK_NULL:
			case self::TK_NUMBER:
			case self::TK_BOOL:
				// sets the handler
				$handler = $this->scalarHandler;
				break;
		}
		
		// fire the handler if set
		if(!is_null($handler)) {
			call_user_func_array($handler, array(
				$token->value, // token value
				is_null($relatedProperty) ? null : $relatedProperty->value // related property value or null if not applicable
			));
		}
	}
	
	/**
	 * Determines if a given token code is acceptable for the current state of the parser.
	 * 
	 * @param number $tokenId
	 * @return boolean <code>true</code> if the token is acceptable, <code>false</code> otherwise
	 */
	private function validateToken($tokenId) {
		if($tokens = $this->getAcceptableTokens()) {
			return in_array($tokenId, $tokens);
		}
		
		return false;
	}
	
	/**
	 * Return all the currently acceptable tokens given the current parser state and active token.
	 * 
	 * @return array list of acceptable token codes
	 */
	private function getAcceptableTokens() {
		$state = $this->getCurrentState();
		$stateData = isset(self::$ALLOWED_NEXT_TOKENS[$state]) ? self::$ALLOWED_NEXT_TOKENS[$state] : null;
		if(is_null($stateData)) {
			return null;
		}
		
		return isset($stateData[$this->currentTokenId]) ? $stateData[$this->currentTokenId] : null;
	}
	
	/**
	 * Returns the currently active parser state code.
	 * 
	 * @return number
	 */
	private function getCurrentState() {
		return $this->stateStack[0];
	}
	
	/**
	 * Sets callback function for properties.
	 * 
	 * The callback function expects two arguments($value, $property).
	 * 
	 * Note: the second argument property will always be null, but the form is kept for consistency.
	 * 
	 * @param callable $propertyHandler
	 */
	public function setPropertyHandler($propertyHandler) {
		$this->propertyHandler = $propertyHandler;
	}
	
	/**
	 * Sets callback functions for start and end of objects.
	 * 
	 * The callback function expects two arguments($value, $property).
	 * 
	 * @param callable $objectStartHandler
	 * @param callable $objectEndHandler
	 */
	public function setObjectHandlers($objectStartHandler, $objectEndHandler) {
		$this->objectStartHandler = $objectStartHandler;
		$this->objectEndHandler = $objectEndHandler;
	}
	
	/**
	 * Sets callback functions for start and end of arrays.
	 * 
	 * The callback function expects two arguments($value, $property).
	 * 
	 * @param callable $arrayStartHandler
	 * @param callable $arrayEndHandler
	 */
	public function setArrayHandlers($arrayStartHandler, $arrayEndHandler) {
		$this->arrayStartHandler = $arrayStartHandler;
		$this->arrayEndHandler = $arrayEndHandler;
	}
	
	/**
	 * Sets callback function for scalar values (string, number, boolean, null).
	 * 
	 * The callback function expects two arguments($value, $property).
	 * 
	 * @param callable $scalarHandler
	 */
	public function setScalarHandler($scalarHandler) {
		$this->scalarHandler = $scalarHandler;
	}
}
