#!/bin/bash

# We echo the command line and expand variables for easier debuging...
set -x

# This is used by Travis CI to deploy automatically to SVN when a new tag is created!

# Get a sense of where we are for debuging
pwd

# Clone complete SVN repository to separate directory
svn co $SVN_REPOSITORY ../svn

# Clean-up!
rm -rf ../svn/trunk/
rm -rf ../svn/assets/
rm -rf ../svn/tags/$TRAVIS_TAG/

# Recreate directories after clean-up
mkdir -p ../svn/trunk/
mkdir -p ../svn/assets/
mkdir -p ../svn/tags/$TRAVIS_TAG/

echo "Listing svn dir after clean-up."
ls -la ../svn/

# Copy plugin src contents to SNV trunk/ directory
cp -r ./src/ ../svn/trunk/
cp -r ./assets/ ../svn/assets/

# Go to SVN repository root and list
cd ../svn/

echo "Listing svn dir after copy of trunk and assets."
ls -la

# Create SVN tag
cp -r trunk/ tags/$TRAVIS_TAG/

# Add svn tag
svn add tags/$TRAVIS_TAG/
svn status

# Push SVN tag
svn ci  --message "Release $TRAVIS_TAG" \
        --username $SVN_USERNAME \
        --password $SVN_PASSWORD \
        --non-interactive
