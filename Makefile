install:
	composer install

load:
	composer dump-autoload

console:
	composer exec --verbose psysh

lint:
	composer exec --verbose phpcs -- --standard=PSR12 src tests parsers
	composer exec --verbose phpstan

lint-fix:
	composer exec --verbose phpcbf -- --standard=PSR12 src tests

test:
	composer exec --verbose phpunit tests

test-coverage:
	XDEBUG_MODE=coverage composer exec --verbose phpunit tests -- --coverage-clover build/logs/clover.xml

test-coverage-text:
	XDEBUG_MODE=coverage composer exec --verbose phpunit tests -- --coverage-text