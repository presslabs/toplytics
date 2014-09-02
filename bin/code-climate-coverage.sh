#!/bin/bash
set -e
CODECLIMATE_REPO_TOKEN=67ceec44d9c9180b3be8f426ca74efb9362fc1a441aa6d25cea46245dd34aca9 vendor/bin/test-reporter --stdout > codeclimate.json
curl -X POST -d @codeclimate.json -H 'Content-Type: application/json' -H 'User-Agent: Code Climate (PHP Test Reporter v1.0.1-dev)' https://codeclimate.com/test_reports
