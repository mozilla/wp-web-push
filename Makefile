.PHONY: reinstall test

WP_CLI = tools/wp-cli.phar
PHPUNIT = tools/phpunit.phar

reinstall: $(WP_CLI)
	$(WP_CLI) plugin uninstall --deactivate wp-web-push --path=$(WORDPRESS_PATH)
	rm -f wp-web-push.zip
	zip wp-web-push.zip -r wp-web-push/
	$(WP_CLI) plugin install --activate wp-web-push.zip --path=$(WORDPRESS_PATH)

test: $(PHPUNIT)
	$(PHPUNIT)

generate-pot:
	php $(WORDPRESS_REPO_PATH)/tools/i18n/makepot.php wp-plugin wp-web-push
	mv wp-web-push.pot wp-web-push/lang/web-push.pot

tools/wp-cli.phar:
	mkdir -p tools
	wget -P tools -N https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	chmod +x $(WP_CLI)

tools/phpunit.phar:
	mkdir -p tools
	wget -P tools -N https://phar.phpunit.de/phpunit-old.phar
	mv tools/phpunit-old.phar tools/phpunit.phar
	chmod +x $(PHPUNIT)

