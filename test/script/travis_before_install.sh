#!/usr/bin/env bash

set -e
trap '>&2 echo Error: Command \`$BASH_COMMAND\` on line $LINENO failed with exit code $?' ERR

## backup and disable xdebug
cp ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/xdebug.ini ~/.phpenv/versions/$(phpenv version-name)/xdebug.ini.bak
echo > ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/xdebug.ini
phpenv rehash

echo "Current path: "
pwd

## add certificate
# download it
wget https://curl.haxx.se/ca/cacert.pem

# add cert path to php config
CERTPATH=$(pwd)
echo "curl.cainfo = \"${CERTPATH}/cacert.pem\"" >> ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini

echo "travis.ini"
cat ~/.phpenv/versions/$(phpenv version-name)/etc/conf.d/travis.ini

## create directories for the tests
mkdir -p build/logs

composer self-update
