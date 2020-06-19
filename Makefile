static: static-phpstan static-codestyle-check

static-phpstan:
	docker run --rm -it -e REQUIRE_DEV=true -v ${PWD}:/app -w /app oskarstark/phpstan-ga:0.12.28 analyze $(PHPSTAN_PARAMS)

static-phpstan-update-baseline:
	$(MAKE) static-phpstan PHPSTAN_PARAMS="--generate-baseline"

static-codestyle-fix:
	docker run --rm -it -v ${PWD}:/app -w /app oskarstark/php-cs-fixer-ga:2.16.3.1 --diff-format udiff $(CS_PARAMS)

static-codestyle-check:
	$(MAKE) static-codestyle-fix CS_PARAMS="--dry-run"
