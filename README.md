## Description

This is a PHP implementation of an event based JSON parser.

The idea of implementing this arose when I needed to parse very large JSON files. 
PHP having only json_decode, would choke and utterly die when dealing with 100's of MBs worth of JSON.

It is designed to be light-weight and does its job well enough for me to be ready to release it.

## Code

The final code, if you're looking for it is located in the package folder.

The src folder actually contains the code for the lexer (it needs to be compiled into a PHP lexer via JLexPHP). 
This also explains why the generated Lexer is a bit tough on the eyes.

If you desire to rework and experiment with the lexer, you're welcome to and can make use of the makefile to rebuild it.

## How

Read the test/test.php file for a real example. But basically:

* create a parser instance
* set the handlers for the events you're interested in
* run the parseDocument method
* wait 'n see

## Implementation choices

I first worked on this using lemon-php as parser. 
The problem with that is lemon would wait until an element had been 
fully matched before actually triggering an action.

Of course, it would be very inconvenient since you would only get a notification for the top object once the 
full document got parsed.

I therefore decided to roll my own parser. JSON has a very straight forward syntax and little room for crazyness, so
a dumbed down LL custom made parser does just fine.
 