#!/bin/bash

BASEPATH=$(dirname $(dirname $(realpath $0)));

rm -Rf /tmp/groupoffice-dist
mkdir -p /tmp/groupoffice-dist/groupoffice-server

cp -R $BASEPATH/html /tmp/groupoffice-dist/groupoffice-server
cp -R $BASEPATH/lib /tmp/groupoffice-dist/groupoffice-server
cp -R $BASEPATH/vendor /tmp/groupoffice-dist/groupoffice-server

cd /var/www/html/groupoffice-client/
grunt dist

cp -R /var/www/html/groupoffice-client/dist/app /tmp/groupoffice-dist/html

cd /tmp
tar -czf groupoffice-dist.tgz groupoffice-dist