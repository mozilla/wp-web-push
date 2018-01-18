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
	$(COMPOSER) update
	$(COMPOSER) install --prefer-dist --no-interaction --optimize-autoloader --ignore-platform-reqs
	rm -rf build $(PLUGIN_NAME).zip
	cp -r $(PLUGIN_NAME)/ build/
	cp node_modules/localforage/dist/localforage.nopromises.min.js build/lib/js/localforage.nopromises.min.js
	cp node_modules/chart.js/Chart.min.js build/lib/js/Chart.min.js
	cp -r vendor/ build/
	find build/vendor/ ! \( -iname \*.php -o -iname \*.js \) -type f -exec rm -f {} \;
	find build/vendor/ -type d -empty -delete
	find build/vendor/ -type d \( -name test -o -name tests -o -name examples \) -exec rm -rf {} +
	./node_modules/.bin/svgo -f $(PLUGIN_NAME)/lib/ -o build/lib/ -p 1 --enable=cleanupNumericValues --enable=cleanupListOfValues --enable=convertPathData
	# Need to keep the <?php ... ?>
	cp $(PLUGIN_NAME)/lib/bell.svg build/lib/bell.svg
	for file in `find ./build/lang/ -name "*.po"` ; do msgfmt -o $${file%.*}.mo $$file && rm $$file ; done
	cd build/ && zip $(PLUGIN_NAME).zip -r *
	mv build/$(PLUGIN_NAME).zip $(PLUGIN_NAME).zip

test: $(PHPUNIT) build
	-pkill node
	node tests/server.js &
	$(PHPUNIT)
	-pkill node -n

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

