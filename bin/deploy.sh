#!/usr/bin/env bash

# This is used by Travis CI to deploy automatically to SVN when a new tag is created!

# 1. Clone complete SVN repository to separate directory
svn co $SVN_REPOSITORY ../svn

# 2. Copy plugin src contents to SNV trunk/ directory
cp -R ./src/* ../svn/trunk/

# 3. Copy assets/ to SVN /assets/
cp -R ./assets/ ../svn/assets/

# 4. Switch to SVN repository
cd ../svn/trunk/

# 5. Clean up unnecessary files
# Nothing to clean for now

# 6. Go to SVN repository root
cd ../

# 7. Create SVN tag
svn cp trunk tags/$TRAVIS_TAG

# 8. Push SVN tag
svn ci  --message "Release $TRAVIS_TAG" \
        --username $SVN_USERNAME \
        --password $SVN_PASSWORD \
        --non-interactive
