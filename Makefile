.PHONY: reinstall build npm_install test test-sw release version-changelog

PLUGIN_NAME = wp-web-push
WP_CLI = tools/wp-cli.phar
PHPUNIT = tools/phpunit.phar
COMPOSER = tools/composer.phar

reinstall: $(WP_CLI) build
	$(WP_CLI) plugin uninstall --deactivate $(PLUGIN_NAME) --path=$(WORDPRESS_PATH)
	$(WP_CLI) plugin install --activate $(PLUGIN_NAME).zip --path=$(WORDPRESS_PATH)

build: $(COMPOSER)
	npm install
	$(COMPOSER) install
	rm -rf build $(PLUGIN_NAME).zip
	cp -r $(PLUGIN_NAME)/ build/
	cp node_modules/localforage/dist/localforage.nopromises.min.js build/lib/js/localforage.nopromises.min.js
	cp node_modules/chart.js/Chart.min.js build/lib/js/Chart.min.js
	mkdir -p build/vendor/marco-c/wp-web-app-manifest-generator
	cp vendor/marco-c/wp-web-app-manifest-generator/WebAppManifestGenerator.php build/vendor/marco-c/wp-web-app-manifest-generator/WebAppManifestGenerator.php
	mkdir -p build/vendor/marco-c/WP_Serve_File
	cp vendor/marco-c/WP_Serve_File/class-wp-serve-file.php build/vendor/marco-c/WP_Serve_File/class-wp-serve-file.php
	mkdir -p build/vendor/mozilla/wp-sw-manager
	cp vendor/mozilla/wp-sw-manager/*.php build/vendor/mozilla/wp-sw-manager
	cp -r vendor/mozilla/wp-sw-manager/lib build/vendor/mozilla/wp-sw-manager/
	./node_modules/.bin/svgo -f $(PLUGIN_NAME)/lib/ -o build/lib/ -p 1 --enable=cleanupNumericValues --enable=cleanupListOfValues --enable=convertPathData
	# Need to keep the <?php ... ?>
	cp $(PLUGIN_NAME)/lib/bell.svg build/lib/bell.svg
	cd build/ && zip $(PLUGIN_NAME).zip -r *
	mv build/$(PLUGIN_NAME).zip $(PLUGIN_NAME).zip

test: $(PHPUNIT) build
	$(PHPUNIT)

version-changelog:
	./version-changelog.js $(PLUGIN_NAME)

release: build tools/wordpress-repo version-changelog build

tools/wordpress-repo:
	mkdir -p tools
	cd tools && svn checkout https://develop.svn.wordpress.org/trunk/ && mv trunk wordpress-repo

$(COMPOSER):
	mkdir -p tools
	wget -P tools -N https://getcomposer.org/composer.phar
	chmod +x $(COMPOSER)

$(WP_CLI):
	mkdir -p tools
	wget -P tools -N https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	chmod +x $(WP_CLI)

$(PHPUNIT):
	mkdir -p tools
	wget -P tools -N https://phar.phpunit.de/phpunit-old.phar
	mv tools/phpunit-old.phar $(PHPUNIT)
	chmod +x $(PHPUNIT)

