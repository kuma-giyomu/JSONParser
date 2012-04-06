PACKAGE=package
PARSER_SRC=src
JLEX_HOME=lib/JLexPHP
LEMON_HOME=lib/lemon-php
LEMON=$(LEMON_HOME)/lemon

all: $(PACKAGE)/JSONLex.php $(PACKAGE)/JSONGrammar.php

$(PACKAGE)/JSONLex.php: $(PARSER_SRC)/json.lex.php
	cp $(PARSER_SRC)/json.lex.php $(PACKAGE)/JSONLex.php

$(PARSER_SRC)/json.lex.php: $(JLEX_HOME)/JLexPHP.jar  $(PARSER_SRC)/json.lex
	java -cp $(JLEX_HOME)/JLexPHP.jar JLexPHP.Main $(PARSER_SRC)/json.lex

$(JLEX_HOME)/JLexPHP.jar:
	cd $(JLEX_HOME); make JLexPHP.jar

$(PACKAGE)/JSONGrammar.php: $(PARSER_SRC)/json.php
	cp $(PARSER_SRC)/json.php $(PACKAGE)/JSONGrammar.php

$(PARSER_SRC)/json.php: $(LEMON_HOME)/lemon $(PARSER_SRC)/json.y
	$(LEMON) -lPHP $(PARSER_SRC)/json.y

$(LEMON_HOME)/lemon:
	cd $(LEMON_HOME); make
	
clean:
	rm -f $(PARSER_SRC)/json.lex.php
	rm -f $(PACKAGE)/JSONLex.php
	rm -f $(PARSER_SRC)/json.out $(PARSER_SRC)/json.h $(PARSER_SRC)/json.php
	rm -f $(PACKAGE)/JSONGrammar.php
