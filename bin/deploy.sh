#!/bin/bash

# We echo the command line and expand variables for easier debuging...
set -x

# This is used by Travis CI to deploy automatically to SVN when a new tag is created!

# Clone complete SVN repository to separate directory
svn co $SVN_REPOSITORY ../svn

# Clean-up!
rm -rf ../svn/trunk/
rm -rf ../svn/assets/
rm -rf ../svn/tags/$TRAVIS_TAG/

# Copy plugin src contents to SNV trunk/ directory
cp -R ./src/ ../svn/trunk/
cp -R ./assets/ ../svn/assets/

# Go to SVN repository root
cd ../svn/

# Create SVN tag
mkdir ./tags/$TRAVIS_TAG
svn cp trunk ./tags/$TRAVIS_TAG

# Push SVN tag
svn ci  --message "Release $TRAVIS_TAG" \
        --username $SVN_USERNAME \
        --password $SVN_PASSWORD \
        --non-interactive
