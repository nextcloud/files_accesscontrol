#!/usr/bin/env bash

# SPDX-FileCopyrightText: 2023 Nextcloud GmbH and Nextcloud contributors
# SPDX-License-Identifier: AGPL-3.0-or-later

APP_NAME=files_accesscontrol

APP_INTEGRATION_DIR=$PWD
ROOT_DIR=${APP_INTEGRATION_DIR}/../../../..
composer install

#php -S localhost:8080 -t ${ROOT_DIR} &
#PHPPID=$!
#echo $PHPPID

cp -R ./app "../../../${APP_NAME}_testing"
${ROOT_DIR}/occ app:enable $APP_NAME
${ROOT_DIR}/occ app:enable --force "${APP_NAME}_testing"
${ROOT_DIR}/occ app:enable --force richdocuments
${ROOT_DIR}/occ app:list | grep $APP_NAME

export TEST_SERVER_URL="http://localhost:8080/"
${APP_INTEGRATION_DIR}/vendor/bin/behat --colors -f junit -f pretty $1 $2
RESULT=$?

#kill $PHPPID

${ROOT_DIR}/occ app:disable "${APP_NAME}_testing"
rm -rf "../../../${APP_NAME}_testing"

exit $RESULT
