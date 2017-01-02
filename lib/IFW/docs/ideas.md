# Next meeting:

- Should auth and modules be moved to the core folder as they are essential parts of the core?
- Should sessions be replaced by JSON Web Tokens? How to handle images?
- Filters, how can we setup filtering for all modules.
- Module roadmap
- 

The big idea:

Complete office environment online as simple as possible. Extensible
with modules.

# GroupOffice platform

What should it do?

## Server side
- Database ORM
- Database Patching
- JSON REST API (Routing, Saving, fetching, filtering etc.)
- User authentication
- Roles and permissions
- Tagging system. 
	Tags can be global or module specific. For example "Important" could be a global tag but "Customers" would only be relevant for the contacts module.
	There should also be module specific dynamic tags that can implement specific database queries like age > 35 in the contacts module for example.
- Custom fields
- Module store. Everybody can create modules and deliver them to Intermesh to put them in our module store. Users can browse the store from within Group-Office and click on an install button to install them.
- File uploads
- Image thumbnailing
- E-mail sending
- Reminder / alerts




## Client side
1. Angular JS core components
2. HTML with SCSS / CSS components


# Contacts

- Contact info more visual in email. What contracts does the sender have?
- Putting in relations? Like manager / worker, Father / Son etc. Generate organizational chart of it?

# Projects


# Time tracking

One time entry model that can be related to various items such as:
1. Project
2. Ticket
3. Task
4. E-mail

Perhaps it should have a model name and ID property to be able to store it to any item?
It should of course have customer info too.


#contracts
Notification when contract ends.


# Module store
Everybody can create modules and deliver them to Intermesh to put them in our module store. Users can browse the store from within Group-Office and click on an install button to install them.
Developers can either build the AngularJS client with own script themselves or put the scripts on the server.

## Store module install

Intermesh modules are put in the "Intermesh\Modules" namespace. Custom modules should have the "Store\Modules" namespace. The PHP autoloader function can look in the application data folder for these modules so there are no issues with file permissions. So when a user clicks on "Install" in the store the tarred file "module-name.gom" file is downloaded with "curl" and unpacked in the application data folder.

## Client scripts
The server is able to deliver module scripts to clients. A script can be included:

```````````````````````````````````````````````````````````````````````````````````````````````````````
<script type="text/javascript" src="/groupoffice/api/clientscripts?type=js&client=AngularJS"></script>
```````````````````````````````````````````````````````````````````````````````````````````````````````

This script will gather all *.js files in the installed modules' "ClientScripts/AngularJS" folder and minify them.
CSS files should also be supported.