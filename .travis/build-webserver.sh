#!/usr/bin/env bash

set -e

cd webmon

touch .env

echo "Installing Composer dependencies"
composer install --no-interaction --prefer-dist --no-dev

echo "Installing NPM dependencies"
npm i -g npm@5.8.0
npm ci

echo "Generating static assets"
./node_modules/.bin/webpack --no-progress --hide-modules --config=node_modules/laravel-mix/setup/webpack.config.js

cd ..

docker login -u="$DOCKER_USERNAME" -p="$DOCKER_PASSWORD"
export TAG=`if [ "$TRAVIS_BRANCH" == "master" ]; then echo "latest"; else echo $TRAVIS_BRANCH | tr / - ; fi`
docker build -t anroots/webmon:$TAG .

docker push anroots/webmon:$TAG
docker rmi anroots/webmon:$TAG

docker logout
