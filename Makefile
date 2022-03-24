.PHONY: test
test:
	./vendor/bin/phpunit --stop-on-failure tests/ORM
	./vendor/bin/phpunit --stop-on-failure tests/SQLBuilder