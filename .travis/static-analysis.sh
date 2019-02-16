#!/usr/bin/env bash

set -e

cd webmon
composer install --no-interaction --dev
php vendor/bin/parallel-lint --exclude vendor .
