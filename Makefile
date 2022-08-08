PROJECT_NAME := toplytics
PROJECT_REPO := github.com/presslabs/toplytics

include build/makelib/common.mk
include build/makelib/wordpress.mk
include build/makelib/php.mk

WP_CLI_VERSION:=2.6.0
WP_CLI_DOWNLOAD_URL:=https://github.com/wp-cli/wp-cli/releases/download/v$(WP_CLI_VERSION)/wp-cli-$(WP_CLI_VERSION).phar
$(eval $(call tool.download,wp,$(WP_CLI_VERSION),$(WP_CLI_DOWNLOAD_URL)))

# override php tests because of wordpress.mk
.php.test.init:
	@$(INFO) test wordpress pass
