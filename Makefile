.PHONY: reinstall test

WP_CLI = tools/wp-cli.phar
PHPUNIT = tools/phpunit.phar

reinstall: $(WP_CLI)
	$(WP_CLI) plugin uninstall --deactivate wp-web-push --path=$(WORDPRESS_PATH)
	rm -rf build wp-web-push.zip
	cp -r wp-web-push/ build/
	mv node_modules/localforage/dist/localforage.min.js build/lib/js/localforage.min.js
	mv node_modules/chart.js/Chart.min.js build/lib/js/Chart.min.js
	mv vendor/marco-c/wp-web-app-manifest-generator/WebAppManifestGenerator.php build/WebAppManifestGenerator.php
	zip wp-web-push.zip -r build/
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

