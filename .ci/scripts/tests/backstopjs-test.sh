#!/bin/bash

# Variables
GITHUB_API_URL="https://api.github.com/repos/$CI_PROJECT_USERNAME/$CI_PROJECT_REPONAME"

mkdir -p "${TEMP_DIR}/workspace"

# Check if we are NOT on the master branch and this is a PR
if [[ ${CI_BRANCH} != "master" && -z ${PR_NUMBER} ]];
then
  echo -e "\nVisual regression tests will only run if we are not on the master branch and making a pull request"
  touch ${TEMP_DIR}/workspace/vr_bash_env.txt
  exit 0;
fi

echo -e "\nProcessing env: $TERMINUS_ENV"

# Ping the multidev environment to wake it from sleep
echo -e "\nPinging the ${TERMINUS_ENV} multidev environment to wake it from sleep..."
curl -I "$MULTIDEV_SITE_URL" >/dev/null

# Ping the DEV environment to wake it from sleep
echo -e "\nPinging the ${BACKSTOP_REF_ENV} environment to wake it from sleep..."
curl -I "https://$BACKSTOP_REF_ENV-$TERMINUS_SITE.pantheonsite.io/" >/dev/null

# set cookies
echo -e "\nSetting Cookies..."
node ./tests/backstopjs/backstopjs-generate-cookies.js

# Check for custom config file
echo -e "\nChecking for Custom config file..."
if [ ! -f ./tests/backstopjs/backstop.json ]; then
	# Create Backstop config file from template if needed
	echo -e "\nUsing defaul config file..."
	node ./tests/config-gen/build-test-config-files.js --incTest=backstopjs
fi

# Backstop visual regression
echo -e "\nRunning backstop reference on ${DEV_SITE_URL}..."
./node_modules/.bin/backstop reference --config=./tests/backstopjs/backstop.json

# Kill any zombie Chrome instances
pkill -f "(chrome)?(--headless)"

# Backstop test
echo -e "\nRunning backstop test on ${MULTIDEV_SITE_URL}..."
VISUAL_REGRESSION_RESULTS=$(./node_modules/.bin/backstop test --config=./tests/backstopjs/backstop.json || echo 'true')

echo "${VISUAL_REGRESSION_RESULTS}"

# Create the artifacts directory if needed
if [ ! -d "$ARTIFACTS_FULL_DIR" ]; then
  mkdir -p $ARTIFACTS_FULL_DIR
fi

cp ./tests/backstopjs/backstop.json backstop_data

# Copy backstop_data files to ARTIFACTS_FULL_DIR
echo -e "\nCopying backstop_data files to $ARTIFACTS_FULL_DIR..."
rm -rf $ARTIFACTS_FULL_DIR/backstop_data
cp -r backstop_data $ARTIFACTS_FULL_DIR/

DIFF_IMAGE=$(find ./backstop_data -type f -name "*.png" | grep diff | grep desktop | head -n 1)

if [ ! -f $DIFF_IMAGE ]; then
	echo -e "\nDiff image file $DIFF_IMAGE not found!"
fi

DIFF_IMAGE_URL="$CIRCLE_ARTIFACTS_URL/$DIFF_IMAGE"

DIFF_REPORT="$ARTIFACTS_FULL_DIR/backstop_data/html_report/index.html"

if [ ! -f $DIFF_REPORT ]; then
	echo -e "\nDiff report file $DIFF_REPORT not found!"
	exit 1
fi

# DIFF_REPORT_URL="$CIRCLE_ARTIFACTS_URL/backstop_data/html_report/index.html"

# REPORT_LINK="[![Visual report]($DIFF_IMAGE_URL)]($DIFF_REPORT_URL)"
# REPORT_LINK="[Visual Regression Report $ICON_ARROW]($DIFF_REPORT_URL)"


VR_PR_MESSAGE="## $ICON_REPORT BackstopJS Report:<br><br>"
echo "## $ICON_REPORT BackstopJS Report:" >> message.md
echo "" >> message.md
echo "" >> message.md

if [[ ${VISUAL_REGRESSION_RESULTS} == *"Mismatch errors found"* ]]
then
	# visual regression failed
	echo -e "\nVisual regression test failed!"
	VR_PR_MESSAGE="$VR_PR_MESSAGE$ICON_FAILED **Visual regression test failed!**"
	echo "$ICON_FAILED **Visual regression test failed!**" >> message.md
	echo "" >> message.md
else
	# visual regression passed
	echo -e "\nVisual regression test passed!"
	VR_PR_MESSAGE="$VR_PR_MESSAGE$ICON_PASSED **Visual regression test passed!**"
	echo "$ICON_PASSED **Visual regression test passed!**" >> message.md
	echo "" >> message.md
fi

# VR_PR_MESSAGE="$VR_PR_MESSAGE \n\n[CircleCI Job $CI_BUILD_NUMBER $ICON_ARROW]($CI_BUILD_URL)\n\n"

# # Set gloabl vars for this job.
# VR_BASH_ENV=${VR_BASH_ENV:-$HOME/.vrbashrc}
# (
#   echo "export VR_PR_MESSAGE='$VR_PR_MESSAGE'"
# ) >> $VR_BASH_ENV

# echo 'Contents of BASH_ENV:'
# cat $VR_BASH_ENV
# echo

# cp $VR_BASH_ENV  ${TEMP_DIR}/workspace/vr_bash_env.txt

echo $VR_PR_MESSAGE

(
  echo "VR_PR_MESSAGE=$VR_PR_MESSAGE"
) >> $GITHUB_ENV

# Post the image back to the pull request on GitHub
# echo -e "\nPosting visual regression results back to PR #$PR_NUMBER "
# curl -s -i -u "$CI_PROJECT_USERNAME:$GITHUB_TOKEN" -d "{\"body\": \"$PR_MESSAGE\"}" $GITHUB_API_URL/issues/$PR_NUMBER/comments > /dev/null

# Lets not fail the job. We just want to see any differences.
# if [[ ${VISUAL_REGRESSION_RESULTS} == *"Mismatch errors found"* ]]
# then
#     exit 1
# fi
