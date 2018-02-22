
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

plug: cleanplug
	@echo "Coping fresh plugin code ...\n"
	@cp -r ./toplytics/ ./public/wp-content/plugins/.
	@echo "Done!"

cleanplug:
	@echo "Cleaning up old plugin ...\n"
	@rm -rf

wpinit: wpinstall
	@echo "Applying wp-config to the new installed wordpress ...\n"
	@cp ./wp-config.php.provider ./public/wp-config.php

wpinstall: cleanwp
	@echo "Installing a new wordpress ...\n"
	@WP_CORE_DIR=./public/ WP_TESTS_DIR=./wordpress-tests-lib/ \
		./bin/install-wp-tests.sh wordpress_test default secret mysql latest true
	@echo "WP is now installed in ./wordpress and tests in ./wordpress-tests-lib !\n"

cleanwp:
	@echo "Cleaning the old wordpress...\n"
	@sudo rm -rf wordpress-tests-lib
	@sudo rm -rf public

init: laradock-config wpinit
	@echo "Everything is ready!\n"

laradock-config: laradock-install
	@echo "Applying your configuration files ...\n"
	@cp ./Makefile.provider ./laradock/Makefile
	@cp ./.env.provider ./laradock/.env

laradock-install: clean-laradock
	@echo "Installing a new one ...\n"
	@mkdir laradock
	@mkdir docs/docs-theme
	@git submodule init -q
	@git submodule update --remote -q

clean-laradock:
	@echo "\nCleaning the old laradock ...\n"
	@sudo rm -rf laradock
	@sudo rm -rf docs/docs-theme

.PHONY: up down log bash init plug cleanplug mysql wpinit wpinstall cleanwp \
	clean-laradocks laradock-install laradock-config
