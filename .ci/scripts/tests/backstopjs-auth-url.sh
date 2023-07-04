#!/bin/bash

REF_LOGIN_URL=$(terminus drush ${TERMINUS_SITE}.${BACKSTOP_REF_ENV} -- uli --name=admin)
echo $REF_LOGIN_URL

TEST_LOGIN_URL=$(terminus drush ${TERMINUS_SITE}.${BACKSTOP_TEST_ENV} -- uli --name=admin)
echo $TEST_LOGIN_URL

jq -n --arg ref_url "$REF_LOGIN_URL" --arg test_url "$TEST_LOGIN_URL" '{ref_login_url: $ref_url, test_login_url: $test_url}' > "./tests/backstopjs/login_urls.json"
