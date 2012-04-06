PACKAGE=package
PARSER_SRC=src
JLEX_HOME=lib/JLexPHP
LEMON_HOME=lib/lemon-php
LEMON=$(LEMON_HOME)/lemon

all: package

package: $(PACKAGE)/JLexBase.php $(PACKAGE)/JSONLex.php $(PACKAGE)/JSONGrammar.php
	
	
$(PACKAGE)/JLexBase.php:
	cp $(JLEX_HOME)/jlex.php $(PACKAGE)/JLexBase.php 

$(PACKAGE)/JSONLex.php: $(PARSER_SRC)/JSONLex.lex.php
	cp $(PARSER_SRC)/JSONLex.lex.php $(PACKAGE)/JSONLex.php

$(PARSER_SRC)/JSONLex.lex.php: $(JLEX_HOME)/JLexPHP.jar  $(PARSER_SRC)/JSONLex.lex
	java -cp $(JLEX_HOME)/JLexPHP.jar JLexPHP.Main $(PARSER_SRC)/JSONLex.lex

$(JLEX_HOME)/JLexPHP.jar:
	cd $(JLEX_HOME); make JLexPHP.jar

$(PACKAGE)/JSONGrammar.php: $(PARSER_SRC)/JSONGrammar.php
	cp $(PARSER_SRC)/JSONGrammar.php $(PACKAGE)/JSONGrammar.php

$(PARSER_SRC)/JSONGrammar.php: $(LEMON_HOME)/lemon $(PARSER_SRC)/JSONGrammar.y
	$(LEMON) -lPHP $(PARSER_SRC)/JSONGrammar.y

$(LEMON_HOME)/lemon:
	cd $(LEMON_HOME); make
	
clean:
	rm -f $(PARSER_SRC)/JSONLex.lex.php
	rm -f $(PACKAGE)/JSONLex.php
	rm -f $(PARSER_SRC)/JSONGrammar.out $(PARSER_SRC)/JSONGrammar.h $(PARSER_SRC)/JSONGrammar.php
	rm -f $(PACKAGE)/JSONGrammar.php

test: package
	php test/test.php