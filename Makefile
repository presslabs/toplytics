
up:
	@$(MAKE) -C laradock up

down:
	@$(MAKE) -C laradock down

log:
	@$(MAKE) -C laradock log

bash:
	@$(MAKE) -C laradock bash

mysql:
	@$(MAKE) -C laradock mysql

init: laradock-config
	@echo "Everything is ready!\n"

laradock-config: laradock-install
	@echo "Applying your configuration files ...\n"
	@cp ./Makefile.provider ./laradock/Makefile
	@cp ./.env.laradock ./laradock/.env

laradock-install: clean-laradock
	@echo "Installing a new one ...\n"
	@mkdir laradock
	@mkdir docs/docs-theme
	@git submodule add https://github.com/Laradock/laradock.git -q
	@git submodule init -q
	@git submodule update --remote -q

clean-laradock:
	@echo "\nCleaning the old laradock ...\n"
	@sudo rm -rf laradock
	@sudo rm -rf docs/docs-theme


.PHONY: up down log bash init mysql \
	clean-laradocks laradock-install laradock-config
