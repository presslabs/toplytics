
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
        name: "test",
        image: "docker.io/presslabs/php-runtime:%s" % php_version,
        user: "root",
        environment: {
          WORDPRESS_TEST_DB_HOST: "database"
        },
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
