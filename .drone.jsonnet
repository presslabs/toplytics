
local Pipeline(php_version, wp_version) =
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
        name: "install deps",
        image: "quay.io/presslabs/build:latest",
        environment: {
          WP_CORE_DIR: "/workspace/presslabs/toplytics/wordpress",
          WP_TEST_DIR: "/workspace/presslabs/toplytics/wordpress-test-lib",
        },
        commands: [
          // install build deps
          "make build.tools",
        ],
      },
      {
        name: "test",
        image: "docker.io/presslabs/php-runtime:%s" % php_version,
        commands: [
          "make test WP_VERSION=%s" % wp_version,
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
  Pipeline('8.0', '6.0.1'),
  //Pipeline('7.4'),
]
