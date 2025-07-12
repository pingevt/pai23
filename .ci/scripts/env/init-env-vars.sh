#!/bin/bash
set -eo pipefail

# include common funcs
. ./.ci/scripts/lib/myFuncs

# printenv

node ./.ci/scripts/env/init-env-vars.cjs

#
# Before calling this script, set the following environent variables:
#
#   - CI_BRANCH: the branch being tested
#   - CI_BUILD_NUMBER: monotonically increasing build counter
#   - PR_NUMBER: pull request number (if job is from a pull request)
#
# Optionally:
#
#   - CI_PULL_REQUEST: URL to the current pull request; used to set PR_NUMBER
#   - DEFAULT_SITE: name of the repository; used to set TERMINUS_SITE
#
# Note that any environment variable given above is not set, then
# it will be assigned its value from the corresponding CircleCI
# environment variable.
#


# TZ: "/usr/share/zoneinfo/America/New_York"
TZ=$(cat .projectconfig.cjson | jq -r '.TZ')
if [ "$TZ" == "null" ]
then
  TZ="/usr/share/zoneinfo/America/New_York"
fi

# TEMP_DIR: "../"
TEMP_DIR=$(cat .projectconfig.cjson | jq -r '.TEMP_DIR')
if [ "$TEMP_DIR" == "null" ]
then
  TEMP_DIR="../"
fi

# CMS_PLATFORM: D9
CMS_PLATFORM=$(cat .projectconfig.cjson | jq -r '.CMS_PLATFORM')
if [ "$CMS_PLATFORM" == "null" ]
then
  CMS_PLATFORM="D9"
fi

# DEFAULT_SITE: ""
DEFAULT_SITE=$(cat .projectconfig.cjson | jq -r '.DEFAULT_SITE')
if [ "$DEFAULT_SITE" == "null" ]
then
  DEFAULT_SITE=""
fi

# CI_BRANCH=${CI_BRANCH:-$CIRCLE_BRANCH}
# CI_BUILD_NUMBER=${CI_BUILD_NUMBER:-$CIRCLE_BUILD_NUM}
# CI_PROJECT_NAME=${CI_PROJECT_NAME:-CIRCLE_PROJECT_REPONAME}

# Circle sets both $CIRCLE_PULL_REQUEST and $CI_PULL_REQUEST.
# PR_NUMBER=${PR_NUMBER:-$CI_PULL_REQUEST}
# PR_NUMBER=${PR_NUMBER##*/}
PR_NUMBER=$(gh pr view --json number -q .number || echo "")
CI_PR_URL=$(gh pr view --json url -q .url || echo "")

# Set up BASH_ENV if it was not set for us.
# BASH_ENV=${BASH_ENV:-$HOME/.bashrc}

# Provide a default email address
GIT_EMAIL=${GIT_EMAIL:-ci-bot@pantheon.io}

# Provide latest git message
LATEST_GIT_MSG=$(git log -1 --pretty=%B)

# We will also set the default site name to be the same as the repository name.
# DEFAULT_SITE=${DEFAULT_SITE:-$CI_PROJECT_NAME}

# By default, we will make the main branch master.
# DEFAULT_BRANCH=${DEFAULT_BRANCH:-master}

# # Get default branch from GITHUB.
# mkdir -p git_data
# GITHUB_REPO_URL="https://api.github.com/repos/$CIRCLE_PROJECT_USERNAME/$CIRCLE_PROJECT_REPONAME"
# echo -e ${GITHUB_REPO_URL}
# curl -s -H "Authorization: token ${GITHUB_TOKEN}" ${GITHUB_REPO_URL} > git_data/git_repo_data.json
# DEFAULT_BRANCH=$(cat git_data/git_repo_data.json | jq -r '.default_branch')
# echo -e "\nRsyncing git_data files to /tmp/workspace..."
# rsync -rlvz git_data /tmp/workspace
# echo -e "\nRsyncing git_data files to /tmp/artifacts..."
# rsync -rlvz git_data /tmp/artifacts

# Plan proper env and if we should process jobs...
SHOULD_BUILD_JOB=true

CLONE_CONTENT=false

if [[ ${CI_BRANCH} == "master" ]] ; then
  # Use dev as the environment.
	DEFAULT_ENV=${DEFAULT_ENV:-dev}
  CLONE_CONTENT=true
elif [[ ${CI_BRANCH} == ${DEFAULT_BRANCH} ]] ; then
  DEFAULT_ENV="default-md"
  CLONE_CONTENT=true
elif [[ $CI_BRANCH == release/* ]]; then
  # If release branch.
  DEFAULT_ENV=$(relBranchName $CI_BRANCH)

  # Build only if PR is open.
  if [[ -n ${PR_NUMBER} ]] ; then
    SHOULD_BUILD_JOB=true
  else
    SHOULD_BUILD_JOB=false
  fi

  # Check git message to see if we should clone content.
  if [[ $LATEST_GIT_MSG == *"[clone-content]"* ]]; then
    CLONE_CONTENT=true
  else
    CLONE_CONTENT=false
  fi
elif [[ $CI_BRANCH == persist/* ]]; then

  # If persist branch.
  DEFAULT_ENV=$(persistBranchName $CI_BRANCH)
  SHOULD_BUILD_JOB=true
  CLONE_CONTENT=false

elif [[ -n ${PR_NUMBER} ]] ; then
  # If there is a PR number provided, though, then we will use it instead.
  DEFAULT_ENV="pr-${PR_NUMBER}"

  # Check git message to see if we should clone content.
  if [[ $LATEST_GIT_MSG == *"[clone-content]"* ]]; then
    CLONE_CONTENT=true
  else
    CLONE_CONTENT=false
  fi
else
  # Otherwise, name the environment after the CI build number.
	DEFAULT_ENV=ci-$CI_BUILD_NUMBER
  SHOULD_BUILD_JOB=false
  CLONE_CONTENT=TRUE
fi

# Set some CI env vars.
# CI_PR_URL=${CI_PR_URL:-$CIRCLE_PULL_REQUEST}
CI_PROJECT_USERNAME=${CI_PROJECT_USERNAME:-$CIRCLE_PROJECT_USERNAME}
CI_PROJECT_REPONAME=${CI_PROJECT_REPONAME:-$CIRCLE_PROJECT_REPONAME}
TERMINUS_SITE=${TERMINUS_SITE:-$DEFAULT_SITE}
TERMINUS_ENV=${TERMINUS_ENV:-$DEFAULT_ENV}

GITHUB_API_URL="https://api.github.com/repos/$CIRCLE_PROJECT_USERNAME/$CIRCLE_PROJECT_REPONAME"

ICON_REPORT="&#x1F4DD;"
ICON_PASSED="&#x1F44D;"
ICON_FAILED="&#x274C;"
ICON_WARNING="&#x26A0;"
ICON_CHECK="&#x2714;"
ICON_ARROW="&#10148;"

# Check Live site URL.
PANTHEON_LIVE_SITE_URL="https://live-$TERMINUS_SITE.pantheonsite.io/"
if [[ -n ${LIVE_DOMAIN} ]] ; then
  LIVE_SITE_URL="https://$LIVE_DOMAIN/"
else
  LIVE_SITE_URL="https://live-$TERMINUS_SITE.pantheonsite.io/"
fi

# BackstopJS Vars
# Reference Defaults to LIVE.
BACKSTOP_REF_ENV=$(cat .projectconfig.cjson | jq -r '.backstopjsReferenceEnv')
if [ "$BACKSTOP_REF_ENV" == "null" ]
then
  BACKSTOP_REF_ENV="live"
fi
BACKSTOP_TEST_ENV=$DEFAULT_ENV

# PHP Version
# parse_yaml pantheon.yml "PANTH_" # DEBUGGING
eval $(parse_yaml pantheon.yml "PANTH_")

PHP_VERSION=7.4
if [[ -n ${PANTH_php_version} ]] ; then
  PHP_VERSION=$PANTH_php_version
fi

#=====================================================================================================================
# EXPORT needed environment variables
#
# Circle CI 2.0 does not yet expand environment variables so they have to be manually EXPORTed
# Once environment variables can be expanded this section can be removed
# See: https://discuss.circleci.com/t/unclear-how-to-work-with-user-variables-circleci-provided-env-variables/12810/11
# See: https://discuss.circleci.com/t/environment-variable-expansion-in-working-directory/11322
# See: https://discuss.circleci.com/t/circle-2-0-global-environment-variables/8681
# Bitbucket has similar issues:
# https://bitbucket.org/site/master/issues/18262/feature-request-pipeline-command-to-modify
#=====================================================================================================================
# (
#   echo 'export PATH=$PATH:$HOME/bin'
#   echo "export PR_NUMBER=$PR_NUMBER"
#   echo "export CI_BRANCH=$(echo $CI_BRANCH | grep -v '"'^\(master\|[0-9]\+.x\)$'"')"
#   echo "export DEFAULT_SITE='$DEFAULT_SITE'"
#   echo "export CI_PR_URL='$CI_PR_URL'"
#   echo "export CI_PROJECT_USERNAME='$CI_PROJECT_USERNAME'"
#   echo "export CI_PROJECT_REPONAME='$CI_PROJECT_REPONAME'"
#   echo "export DEFAULT_ENV='$DEFAULT_ENV'"
#   echo 'export TERMINUS_HIDE_UPDATE_MESSAGE=1'
#   echo "export TERMINUS_SITE='$TERMINUS_SITE'"
#   echo "export TERMINUS_ENV='$TERMINUS_ENV'"
#   echo "export DEFAULT_BRANCH='$DEFAULT_BRANCH'"
#   # TODO: Reconcile with environment variables set by build:project:create
#   echo 'export BEHAT_ADMIN_PASSWORD=$(openssl rand -base64 24)'
#   echo 'export BEHAT_ADMIN_USERNAME=pantheon-ci-testing-$CI_BUILD_NUMBER'
#   echo 'export BEHAT_ADMIN_EMAIL=no-reply+ci-$CI_BUILD_NUMBER@getpantheon.com'
#   echo "export MULTIDEV_SITE_URL='https://$TERMINUS_ENV-$TERMINUS_SITE.pantheonsite.io/'"
#   echo "export DEV_SITE_URL='https://dev-$TERMINUS_SITE.pantheonsite.io/'"
#   echo "export TEST_SITE_URL='https://test-$TERMINUS_SITE.pantheonsite.io/'"
#   echo "export LIVE_SITE_URL='$LIVE_SITE_URL'"
#   echo "export PANTHEON_LIVE_SITE_URL='$PANTHEON_LIVE_SITE_URL'"
#   echo "export ARTIFACTS_DIR='artifacts'"
#   echo "export ARTIFACTS_FULL_DIR='/tmp/artifacts'"
#   echo "export SHOULD_BUILD_JOB=$SHOULD_BUILD_JOB"
#   echo "export CLONE_CONTENT=$CLONE_CONTENT"
#   echo "export GITHUB_API_URL=$GITHUB_API_URL"
#   echo "export LATEST_GIT_MSG='$LATEST_GIT_MSG'"
#   echo "export BACKSTOP_REF_ENV='$BACKSTOP_REF_ENV'"
#   echo "export BACKSTOP_TEST_ENV='$BACKSTOP_TEST_ENV'"
#   echo "export ICON_REPORT='$ICON_REPORT'"
#   echo "export ICON_PASSED='$ICON_PASSED'"
#   echo "export ICON_FAILED='$ICON_FAILED'"
#   echo "export ICON_WARNING='$ICON_WARNING'"
#   echo "export ICON_CHECK='$ICON_CHECK'"
#   echo "export ICON_ARROW='$ICON_ARROW'"
# ) >> $BASH_ENV

ARTIFACTS_DIR="artifacts"
mkdir -p "${TEMP_DIR}${ARTIFACTS_DIR}"
ARTIFACTS_FULL_DIR=$( resolve_relative_path "${TEMP_DIR}${ARTIFACTS_DIR}" )


(
  # echo 'PATH=$PATH:$HOME/bin'
  echo "TZ=$TZ"
  echo "TEMP_DIR=$TEMP_DIR"
  echo "CMS_PLATFORM=$CMS_PLATFORM"
  echo "DEFAULT_SITE=$DEFAULT_SITE"
  echo "PR_NUMBER=$PR_NUMBER"
  echo "CI_BRANCH=$(echo $CI_BRANCH | grep -v '"'^\(master\|[0-9]\+.x\)$'"')"
  echo "CI_PR_URL=$CI_PR_URL"
  echo "CI_PROJECT_USERNAME=$CI_PROJECT_USERNAME"
  echo "CI_PROJECT_REPONAME=$CI_PROJECT_REPONAME"
  echo "DEFAULT_ENV=$DEFAULT_ENV"
  echo "TERMINUS_HIDE_UPDATE_MESSAGE=1"
  echo "TERMINUS_SITE=$TERMINUS_SITE"
  echo "TERMINUS_ENV=$TERMINUS_ENV"
  echo "DEFAULT_BRANCH=$DEFAULT_BRANCH"
  # TODO: Reconcile with environment variables set by build:project:create
  echo "MULTIDEV_SITE_URL=https://$TERMINUS_ENV-$TERMINUS_SITE.pantheonsite.io/"
  echo "DEV_SITE_URL=https://dev-$TERMINUS_SITE.pantheonsite.io/"
  echo "TEST_SITE_URL=https://test-$TERMINUS_SITE.pantheonsite.io/"
  echo "LIVE_SITE_URL=$LIVE_SITE_URL"
  echo "PANTHEON_LIVE_SITE_URL=$PANTHEON_LIVE_SITE_URL"
  echo "ARTIFACTS_DIR=$ARTIFACTS_DIR"
  echo "ARTIFACTS_FULL_DIR=$ARTIFACTS_FULL_DIR"
  echo "SHOULD_BUILD_JOB=$SHOULD_BUILD_JOB"
  echo "CLONE_CONTENT=$CLONE_CONTENT"
  echo "GITHUB_API_URL=$GITHUB_API_URL"
  echo "BACKSTOP_REF_ENV=$BACKSTOP_REF_ENV"
  echo "BACKSTOP_TEST_ENV=$BACKSTOP_TEST_ENV"
  echo "ICON_REPORT=$ICON_REPORT"
  echo "ICON_PASSED=$ICON_PASSED"
  echo "ICON_FAILED=$ICON_FAILED"
  echo "ICON_WARNING=$ICON_WARNING"
  echo "ICON_CHECK=$ICON_CHECK"
  echo "ICON_ARROW=$ICON_ARROW"
  echo "PHP_VERSION=$PHP_VERSION"
) >> $GITHUB_ENV







# If a Terminus machine token and site name are defined
# if [[ -n "$TERMINUS_MACHINE_TOKEN" && -n "$TERMINUS_SITE" ]]
# then

#   # Authenticate with Terminus
#   terminus -n auth:login --machine-token=$TERMINUS_MACHINE_TOKEN > /dev/null 2>&1

#   # Use Terminus to fetch variables
#   TERMINUS_SITE_UUID=$(terminus site:info $TERMINUS_SITE --field=id)

#   # And add those variables to $BASH_ENV
#   (
#     echo "export TERMINUS_SITE_UUID='$TERMINUS_SITE_UUID'"
#   ) >> $BASH_ENV
# fi

# source $BASH_ENV

# echo 'Contents of BASH_ENV:'
# cat $BASH_ENV
# echo

# echo -e "Copying Bash contents to file"
# cp $BASH_ENV  bash_env.txt

# Avoid ssh prompting when connecting to new ssh hosts
# mkdir -p $HOME/.ssh && echo "StrictHostKeyChecking no" >> "$HOME/.ssh/config"

# Configure the GitHub Oauth token if it is available
# if [ -n "$GITHUB_TOKEN" ]; then
#   composer -n config --global github-oauth.github.com $GITHUB_TOKEN
# fi

# Set up our default git config settings if git is available.
# git config --global user.email "${GIT_EMAIL:-no-reply+ci-$CI_BUILD_NUMBER@getpantheon.com}"
# git config --global user.name "CI Bot"
# git config --global core.fileMode false

# echo ${GITHUB_TOKEN}
# echo ${GIT_EMAIL}

# Re-install the Terminus Build Tools plugin if requested
# if [ -n $BUILD_TOOLS_VERSION ] && [ "$BUILD_TOOLS_VERSION" <> 'dev-master' ]; then
#   echo "Install Terminus Build Tools Plugin version $BUILD_TOOLS_VERSION."
#   echo "Note that it is NOT RECOMMENDED to define BUILD_TOOLS_VERSION, save in the Terminus Build Tools plugin tests themselves. All other tests should use the version bundled with the container."
#   rm -rf ${TERMINUS_PLUGINS_DIR:-~/.terminus/plugins}/terminus-build-tools-plugin
#   composer -n create-project --no-dev -d ${TERMINUS_PLUGINS_DIR:-~/.terminus/plugins} pantheon-systems/terminus-build-tools-plugin:$BUILD_TOOLS_VERSION
# fi









