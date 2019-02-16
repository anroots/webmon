#!/usr/bin/env bash

set -e

cd certstream-worker

docker login -u="$DOCKER_USERNAME" -p="$DOCKER_PASSWORD"
export TAG=`if [ "$TRAVIS_BRANCH" == "master" ]; then echo "latest"; else echo $TRAVIS_BRANCH | tr / - ; fi`
docker build -t anroots/webmon-certstream-worker:$TAG .

docker push anroots/webmon-certstream-worker:$TAG
docker rmi anroots/webmon-certstream-worker:$TAG

docker logout
