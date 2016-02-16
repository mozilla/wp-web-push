.PHONY: reinstall build test generate-pot release version-changelog

WP_CLI = tools/wp-cli.phar
PHPUNIT = tools/phpunit.phar
COMPOSER = tools/composer.phar

reinstall: $(WP_CLI) build
	$(WP_CLI) plugin uninstall --deactivate wp-web-push --path=$(WORDPRESS_PATH)
	$(WP_CLI) plugin install --activate wp-web-push.zip --path=$(WORDPRESS_PATH)

build:
	rm -rf build wp-web-push.zip
	cp -r wp-web-push/ build/
	cp node_modules/localforage/dist/localforage.min.js build/lib/js/localforage.min.js
	cp node_modules/chart.js/Chart.min.js build/lib/js/Chart.min.js
	cp vendor/marco-c/wp-web-app-manifest-generator/WebAppManifestGenerator.php build/WebAppManifestGenerator.php
	mkdir -p build/vendor/mozilla/wp-sw-manager
	cp vendor/mozilla/wp-sw-manager/*.php build/vendor/mozilla/wp-sw-manager
	cp -r vendor/mozilla/wp-sw-manager/lib build/vendor/mozilla/wp-sw-manager/
	cd build/ && zip wp-web-push.zip -r *
	mv build/wp-web-push.zip wp-web-push.zip

test: $(PHPUNIT) build
	$(PHPUNIT)

generate-pot: tools/wordpress-repo
	php tools/wordpress-repo/tools/i18n/makepot.php wp-plugin wp-web-push
	mv wp-web-push.pot wp-web-push/lang/web-push.pot

version-changelog:
	./version-changelog.js

release: build version-changelog generate-pot

tools/wordpress-repo:
	cd tools && svn checkout https://develop.svn.wordpress.org/trunk/ && mv trunk wordpress-repo

tools/composer.phar:
	mkdir -p tools
	wget -P tools -N https://getcomposer.org/composer.phar
	chmod +x $(COMPOSER)

tools/wp-cli.phar:
	mkdir -p tools
	wget -P tools -N https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	chmod +x $(WP_CLI)

tools/phpunit.phar:
	mkdir -p tools
	wget -P tools -N https://phar.phpunit.de/phpunit-old.phar
	mv tools/phpunit-old.phar tools/phpunit.phar
	chmod +x $(PHPUNIT)

