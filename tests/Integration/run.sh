#!/usr/bin/env bash

APP_NAME=files_accesscontrol

APP_INTEGRATION_DIR=$PWD
ROOT_DIR=${APP_INTEGRATION_DIR}/../../../..
composer install

#php -S localhost:8080 -t ${ROOT_DIR} &
#PHPPID=$!
#echo $PHPPID

${ROOT_DIR}/occ app:enable $APP_NAME
${ROOT_DIR}/occ app:list | grep $APP_NAME

export TEST_SERVER_URL="http://localhost:8080/"
${APP_INTEGRATION_DIR}/vendor/bin/behat --colors -f junit -f pretty $1 $2
RESULT=$?

#kill $PHPPID

exit $RESULT
