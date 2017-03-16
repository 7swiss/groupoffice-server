#!/bin/bash

cd ../
(git log --since='2016-04-19' --date=iso --pretty=format:'%h%x09%an%x09%ad%x09%s';echo '';) > /tmp/commits.csv

cd GO/Modules/Intermesh
(git log --since='2016-04-19' --date=iso --pretty=format:'%h%x09%an%x09%ad%x09%s';echo '';) >> /tmp/commits.csv
cd ../Instructiefilm
(git log --since='2016-04-19' --date=iso --pretty=format:'%h%x09%an%x09%ad%x09%s';echo '';) >> /tmp/commits.csv
cd ../Houtwerf
(git log --since='2016-04-19' --date=iso --pretty=format:'%h%x09%an%x09%ad%x09%s';echo '';) >> /tmp/commits.csv

cd /var/www/html/groupoffice-webclient
(git log --since='2016-04-19' --date=iso --pretty=format:'%h%x09%an%x09%ad%x09%s';echo '';) >> /tmp/commits.csv

cd app/modules/intermesh
(git log --since='2016-04-19' --date=iso --pretty=format:'%h%x09%an%x09%ad%x09%s';echo '';) >> /tmp/commits.csv
cd ../instructiefilm
(git log --since='2016-04-19' --date=iso --pretty=format:'%h%x09%an%x09%ad%x09%s';echo '';) >> /tmp/commits.csv

cd /var/www/html/elearning-webclient
(git log --since='2016-04-19' --date=iso --pretty=format:'%h%x09%an%x09%ad%x09%s';echo '';) >> /tmp/commits.csv