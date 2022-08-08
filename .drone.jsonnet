
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
        name: "prepare php",
        image: "quay.io/presslabs/build:latest",
        environment: {
          WP_CORE_DIR: "/workspace/presslabs/toplytics/wordpress",
          WP_TEST_DIR: "/workspace/presslabs/toplytics/wordpress-test-lib",
        },
        commands: [
          "make build.tools",
          "make wordpress.build W,P_VERSION=%s" % wp_version,
          "composer require \"phpunit/phpunit=4.8.*|5.7.*\"",
          "composer install -no --prefer-dist --no-dev -d ./src/"
        ],
      },
      {
        name: "test",
        image: "docker.io/presslabs/php-runtime:%s" % php_version,
        commands: [
          "make test",
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
