.PHONY: reinstall

WP_CLI = tools/wp-cli.phar

reinstall: $(WP_CLI) wp-web-push.zip
	$(WP_CLI) plugin uninstall --deactivate wp-web-push --path=$(WORDPRESS_PATH)
	zip wp-web-push.zip -r wp-web-push/
	$(WP_CLI) plugin install --activate wp-web-push.zip --path=$(WORDPRESS_PATH)

tools/wp-cli.phar:
	mkdir -p tools
	wget -P tools -N https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
	chmod +x $(WP_CLI)


