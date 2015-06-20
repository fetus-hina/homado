all: init

init: install-composer depends-install

install-composer: composer.phar

depends-install: install-composer
	php composer.phar install

depends-update: install-composer
	php composer.phar self-update
	php composer.phar update

check-style:
	vendor/bin/phpcs --standard=PSR2 --encoding=UTF-8 src

fix-style:
	vendor/bin/phpcbf --standard=PSR2 --encoding=UTF-8 src

clean:
	rm -rf vendor composer.phar

composer.phar:
	curl -sS https://getcomposer.org/installer | php

FORCE:

.PHONY: all init install-composer depends-install depends-update clean check-style fix-style
