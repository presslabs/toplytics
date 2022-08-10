PROJECT_NAME := toplytics
PROJECT_REPO := github.com/presslabs/toplytics

WP_VERSION ?= 6.0.1

include build/makelib/common.mk
include build/makelib/wordpress.mk
include build/makelib/php.mk

.php.test.init: $(WP_TESTS_DIR)/wp-tests-config.php $(WP_TESTS_DIR)/includes $(WP_TESTS_DIR)/data
