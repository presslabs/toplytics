# build
Presslabs GNU make based build system

## Goals

1. Allow building locally the same way the project is build on CI
2. Provide a sane test, build, publish flow
3. Provide stable toolchain for building (eg. pinned tool versions)
4. Enables caching for speeding up builds.

## Quickstart

```sh
git subtree add -P build https://github.com/presslabs/build.git

cat <<EOF > Makefile
# Project Setup
PROJECT_NAME := mysql-operator
PROJECT_REPO := github.com/presslabs/mysql-operator

include build/makelib/common.mk
```

### Push back changes

An [workaround](https://github.com/rust-lang/rust-clippy/issues/5565#issuecomment-623489754) on how
to bypass the segfault of git subtree command on repose with more commits.

```sh
ulimit -s 60000  # workaround to fix segfault

git subtree push -P build/ git@github.com:presslabs/build.git <a branch name>
```

## Development workflow

The image publishing will work as follows:

On a feature branch (e.g. `feat-*`):
 * Drone build runs without image publishing
 * Can't trigger a promotion (it will fail)

On a release branch (e.i. `release-*` or `master`):
 * Drone build will publish images using git-semver using the following tags: `$(git-semver)`, `$(git-semver)-$ARCH`
 * Manually can promote to the following channels (`$CHANNEL`): `stable`, `beta`, `alpha`, `master`
 * *On promote*: the images are published with the following tags:  `$CHANNEL`, `$CHANNEL-$(git-semver)`, `$(git-semver)`, `$(git-semver)-$ARCH`
 * *On promote* and parameter `PUBLISH_TAG` is set: a new git tag will be created and images will be published under the following tags: `$CHANNEL`, `$CHANNEL-$PUBLISH_TAG`, `$PUBLISH_TAG` (if channel is `stable`).

On git tag event the CI will not run.

## Usage

```
Usage: make [make-options] <target> [options]

Common Targets:
    build              Build source code and other artifacts for host platform.
    build.all          Build source code and other artifacts for all platforms.
    build.tools        Install the required build tools.
    clean              Remove all files created during the build.
    distclean          Remove all files created during the build including cached tools.
    generate           Run code generation tools.
    fmt                Run code auto-formatting tools.
    lint               Run lint and code analysis tools.
    test               Runs unit tests.
    e2e                Runs end-to-end integration tests.
    translate          Collect translation strings and post-process the .pot/.po files.
    help               Show this help info.
```

## Acknowledgement

This work is based on https://github.com/bitpoke/build
This work is based on https://github.com/upbound/build.
