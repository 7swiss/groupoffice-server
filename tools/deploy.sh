#!/bin/bash

#change to script directory
cd "$(dirname "$0")"
cd ..

mkdir -p build
rm -rf build/*

cd build
git clone git@github.com:Intermesh/groupoffice-server.git
cd  groupoffice-server
composer install --no-dev  --optimize-autoloader
cd ..

git clone git@github.com:Intermesh/groupoffice-webclient.git
cd  groupoffice-webclient
npm install
gulp build

mkdir ../groupoffice-server/webclient
mv build/* ../groupoffice-server/webclient

