
local Pipeline(php_version, wp_version = "latest") =
  {
    kind: 'pipeline',
    name: 'php-' + php_version,

    clone: {
      disable: true
    },

    steps: [
      {
        name: "git",
        pull: "default",
        image: "plugins/git",
        settings: {
          depth: 0,
          tags: true
        }
      },
      {
        name: "prepare wordpress",
        image: "debian:latest",
environment: {
          WP_CORE_DIR: "/drone/src/wordpress",
          WP_TEST_DIR: "/drone/src/wordpress-test-lib",
        },
        commands: [
          "pwd",
          "apt update",
          "apt install -y subversion",

          "bash bin/install-wp-tests.sh wordpress_test wordpress wordpress database %s" % wp_version,

        ],
      },
      {
        name: "prepare php",
        image: "docker.io/presslabs/php-runtime:%s" % php_version,
        environment: {
          WP_CORE_DIR: "/workspace/presslabs/toplytics/wordpress",
          WP_TEST_DIR: "/workspace/presslabs/toplytics/wordpress-test-lib",
        },
        commands: [
          "composer global require \"phpunit/phpunit=4.8.*|5.7.*\"",
          "composer install -no --prefer-dist --no-dev -d ./src/"
        ],
      },
      {
        name: "test",
        image: "docker.io/presslabs/php-runtime:%s" % php_version,
        commands: [
          "phpunit",
          //"WP_MULTISITE=1 phpunit",
        ],
      },
      {
        name: "publish",
        image: "quay.io/presslabs/build:latest",
        group: "publish",
        commands: [
          "/usr/local/bin/setup-credentials-helper.sh",
        ],
        when: {
          event: {
            include: ['tag']
          }
        }
      }
    ],

    services: [
      {
        name: "database",
        image: "percona:5.7",
        pull: "always",
        environment: {
          MYSQL_DATABASE: "wordpress_test",
          MYSQL_USER: "wordpress",
          MYSQL_PASSWORD: "wordpress",
          MYSQL_ROOT_PASSWORD: "test"
        }
      }
    ],
  };

[
  Pipeline('8.0'),
  //Pipeline('7.4'),
]
