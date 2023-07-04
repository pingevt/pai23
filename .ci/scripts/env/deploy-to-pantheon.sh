#!/bin/bash

set -eo pipefail

# Create a new multidev site to dev on
terminus -n env:wake "$TERMINUS_SITE.dev"

echo ${CI_BRANCH}
echo ${PR_NUMBER}

if [[ "$CLONE_CONTENT" == true ]]; then
  echo -e "Copying site from dev to $TERMINUS_ENV. Cloning content"
  terminus -n build:env:create "$TERMINUS_SITE.dev" "$TERMINUS_ENV" --yes --clone-content
else
  echo -e "Copying site from dev to $TERMINUS_ENV. NOT cloning content"
  terminus -n build:env:create "$TERMINUS_SITE.dev" "$TERMINUS_ENV" --yes
fi

# Run Drush Commands if not WordPress
if [ "$CMS_PLATFORM" != "WP" ]; then
  # Run updatedb to ensure that the cloned database is updated for the new code.
  terminus -n drush "$TERMINUS_SITE.$TERMINUS_ENV" -- updatedb -y

  # If any modules, or theme files have been moved around or reorganized, in order to avoid
  # "The website encountered an unexpected error. Please try again later." error on First Visit
  terminus -n drush "$TERMINUS_SITE.$TERMINUS_ENV" cr

  # If exported configuration is available, then import it.
  if [ -f "config/system.site.yml" ] ; then
    terminus -n drush "$TERMINUS_SITE.$TERMINUS_ENV" -- config-import --yes
  fi
fi
