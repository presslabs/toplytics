#!/bin/bash

# This is used by Travis CI to deploy automatically to SVN when a new tag is created!

# We echo the command line and expand variables for easier debuging...
set -x

# We make sure tag doesn't already exists
TAG=$(svn ls "$SVN_REPOSITORY/tags/$TRAVIS_TAG")
error=$?
if [ $error == 0 ]; then
    # Tag exists, don't deploy
    echo "Tag already exists for version $TRAVIS_TAG, aborting deployment."
    exit 1
fi

# Clone complete SVN repository to separate directory
svn co -q $SVN_REPOSITORY ../svn

# Clean-up!
rm -rf ../svn/trunk/
rm -rf ../svn/assets/

# Recreate directories after clean-up
mkdir -p ../svn/trunk/
mkdir -p ../svn/assets/

# Copy plugin src and assets to SVN
rsync -r -p ./src/* ../svn/trunk
rsync -r -p ./assets/* ../svn/assets

# Go to SVN repository root and list
cd ../svn/

# Create SVN tag
mkdir -p tags/$TRAVIS_TAG/
rsync -r -p ./trunk/* ./tags/$TRAVIS_TAG

# Add svn new files and delete files
svn stat . | grep '^?' | awk '{print $2}' | xargs -I x svn add x@
svn stat . | grep '^!' | awk '{print $2}' | xargs -I x svn rm --force x@
svn stat .

# Commit & push SVN tag
svn ci  --no-auth-cache \
        --non-interactive \
        --username $SVN_USERNAME \
        --password $SVN_PASSWORD \
        --message "Release $TRAVIS_TAG" \
        .
