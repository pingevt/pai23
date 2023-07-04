#!/bin/bash

set -eo pipefail

PWD_VAR=$(pwd)
UUID=4a1a251a-53ba-4793-a41f-862eb890934c
ENV=live

echo $PWD_VAR

curl -H "api-key: $CONNECT_BC_API" -d "project=$TERMINUS_SITE&branch=$GITHUB_REF_NAME" -X POST https://live-connect-bluecadet.pantheonsite.io/api/vis-reg-result >> connect-bc.js

TIMESTAMP=$(cat connect-bc.js | jq -r '.data.timestamp')

mkdir ../to-be-copied
mkdir ../to-be-copied/$TIMESTAMP
cp -R ../artifacts ../to-be-copied/$TIMESTAMP

cd ../to-be-copied
rsync -raRLvz --relative --size-only --checksum --ipv4 --progress -e 'ssh -p 2222' . --temp-dir=~/tmp/ $ENV.$UUID@appserver.$ENV.$UUID.drush.in:files/vis-reg-reports


echo $PWD_VAR

cd "$PWD_VAR"

VR_PR_LINK="[VR Report](https://live-connect-bluecadet.pantheonsite.io/sites/default/files/vis-reg-reports/$TIMESTAMP/artifacts/backstop_data/html_report/index.html)"

echo $VR_PR_LINK

echo "[VR Report](https://live-connect-bluecadet.pantheonsite.io/sites/default/files/vis-reg-reports/$TIMESTAMP/artifacts/backstop_data/html_report/index.html)" >> message.md
