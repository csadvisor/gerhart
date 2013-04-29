test:
	@TEST=localtest bash test.sh

vim:
	vim -o controllers models

.PHONY: test vim
