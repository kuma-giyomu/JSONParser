PACKAGE=package
PARSER_SRC=src
JLEX_HOME=lib/JLexPHP
LEMON_HOME=lib/lemon-php

all: package

package: $(PACKAGE)/JLexBase.php $(PACKAGE)/JSONLex.php
	
$(PACKAGE)/JLexBase.php:
	cp $(JLEX_HOME)/jlex.php $(PACKAGE)/JLexBase.php 

$(PACKAGE)/JSONLex.php: $(PARSER_SRC)/JSONLex.lex.php
	cp $(PARSER_SRC)/JSONLex.lex.php $(PACKAGE)/JSONLex.php

$(PARSER_SRC)/JSONLex.lex.php: $(JLEX_HOME)/JLexPHP.jar  $(PARSER_SRC)/JSONLex.lex
	java -cp $(JLEX_HOME)/JLexPHP.jar JLexPHP.Main $(PARSER_SRC)/JSONLex.lex

$(JLEX_HOME)/JLexPHP.jar:
	cd $(JLEX_HOME); make JLexPHP.jar

clean:
	rm -f $(PARSER_SRC)/JSONLex.lex.php
	rm -f $(PACKAGE)/JSONLex.php

test: package
	php test/test.php