GroupOffice REST API Server
===========================

To install:

1. Put these sources somewhere outside the web root. For example /var/www/groupoffice-server
2. Make an alias to /var/www/groupoffice-server/public/index.php:
	
	```
	Alias /api /var/www/groupoffice-server/public/index.php

	<Directory /var/www/groupoffice-server/public>
	require all granted
	</Directory> 
	```
3. Create a MySQL database and user for GroupOffice. For example named "go7".

	Here are some example commmands. If you use a root password then add -p.

	```
	$ mysql -u root -e "CREATE DATABASE go7"
	$ mysql -u root -e "GRANT ALL PRIVILEGES ON go7.* TO 'go7'@'localhost' IDENTIFIED BY 'secret' WITH GRANT OPTION"
	```

4. Create the data folder where Group-Office can store files.

	```
	$ mkdir /var/www/groupoffice-server/data
	$ sudo chown www-data:www-data /var/www/groupoffice-server/data
	```

5. Create config.php configuration file by copying the defaults:
	```
	$ cd /var/www/groupoffice-server
	$ cp config.php.example config.php
	```

6. Move webclient into public html folder. For example /var/www/html/webclient

7. Create initial config file
	```
	$ cp /var/www/html/webclient/config.js.example /var/www/html/webclient/config.js
	```
8. Launch the web client in the browser and follow instructions
