#!/bin/bash

set -eo pipefail

# include common funcs
. ./.ci/scripts/lib/myFuncs

terminus whoami

echo "Prepring build for $TERMINUS_ENV. Pantheon test environment is $TERMINUS_SITE.$TERMINUS_ENV\n"

# Report installed version of Terminus
terminus --version

# Delete leftover CI environments
terminus -n build:env:delete:ci "$TERMINUS_SITE" --keep=2 --yes
echo -e "Finished checking CI environments\n"

# Delete leftover PR environments
terminus -n build:env:delete:pr "$TERMINUS_SITE" --yes
echo -e "Finished checking PR environments\n"

# Delete leftover REL environments
deleteReleaseEnvs
echo -e "Finished checking Release environments\n"
