Installation

1. Unpack the tar and put it in /usr/local/nhapi
2. Create database and user called "nhapi"
3. Copy config.php.nh to config.php and adjust database parameters and go5ConfigFile and go5ConfigRoot
4. Create an apache alias in /etc/apache2/conf-enabled/nhapi.conf:

Alias /api /usr/local/nhapi/html/index.php
<Directory /usr/local/nhapi/html>
require all granted
</Directory>


5. Create data folder:

$ mkdir /home/nhapi
$ chown www-data:www-data /home/nhapi


5. Now launch /api in your browser and it should show some JSON output:

{
    "success": true,
    "databaseInstalled": false,
    "installUrl": {},
    "upgradeUrl": {},
    "checks": {
        "PHP version": {
            "validationErrors": [],
            "className": "GO\\Core\\Install\\Model\\SystemCheckResult",
            "success": true,
            "msg": "OK (5.6.4-4ubuntu6.3)"
        },
        "Mcrypt extension": {
            "validationErrors": [],
            "className": "GO\\Core\\Install\\Model\\SystemCheckResult",
            "success": true,
            "msg": "OK"
        },
        "Database connection": {
            "validationErrors": [],
            "className": "GO\\Core\\Install\\Model\\SystemCheckResult",
            "success": true,
            "msg": "Connection established"
        },
        "Temp folder": {
            "validationErrors": [],
            "className": "GO\\Core\\Install\\Model\\SystemCheckResult",
            "success": true,
            "msg": "Is writable"
        },
        "Data folder": {
            "validationErrors": [],
            "className": "GO\\Core\\Install\\Model\\SystemCheckResult",
            "success": true,
            "msg": "Is writable"
        }
    }
}


6. If everything is OK go to /api/system/install and it should return:

{
    "success": true
}

7. Now all is ready and you can use the API. You could use postman to do a login for example. Post this JSON with the default login:

{
    "data" : {
        "username" : "admin",
        "password" : "Admin1!"
    }
}

8. After login you can list matters:

Do a GET request on: 

/api/matters
