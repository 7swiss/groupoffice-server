Creating a module in the AngularJS client
-----------------------------------------

### Create folder structure
1. Create folder "app/modules/helloworld"
2. Create folder "app/modules/helloworld/js" for the script files.
3. Create folder "app/modules/helloworld/views" for the HTML views.

### Create module.js
Create "app/modules/helloworld/module.js" that will initialize the module.

In this file we'll add the states of the module and we can configure it before the application is running.

Example:

```````````````````````````````````````````````````````````````````````````````````````````````````````````````````````
'use strict';

//Use GO.module instead of angular.module so it will be added to the app dependencies
GO.module('GO.helloworld').
		//Create a launcher
		config(['launcherProvider', function (launcherProvider) {								
				launcherProvider.add('helloworld', 'Hello World', []);
			}]).
		config(['$stateProvider', function($stateProvider) {

				// Now set up the states
				$stateProvider
						.state('helloworld', {
							url: "/helloworld",
							templateUrl: 'modules/helloworld/views/main.html',
						});
			}]);
```````````````````````````````````````````````````````````````````````````````````````````````````````````````````````

Make sure "grunt watch" is running so that the scripts are automatically added to app/index.html.


### Create partials/main.html

```````````````````````````````````````````````````````````````````````````````````````````````````````````````````````
<div ng-include="'partials/header.html'"></div>

<div class="iae-full-panel">	
	<div class="iae-toolbar">
		<ul class="iae-toolbar-left">
			<li>
				<button type="submit" class="btn btn-primary">
					<i class="fa fa-floppy-o"></i> A button
				</button>
			</li>

		</ul>
		
	</div>
	
	<h1>Hello World!</h1>
</div>
```````````````````````````````````````````````````````````````````````````````````````````````````````````````````````



### Add the module to the database:

```````````````````````````````````````````
INSERT INTO `ipe`.`modulesModule` (
`id` ,
`name` ,
`type`
)
VALUES (
NULL , 'helloworld', 'user'
);

```````````````````````````````````````````

Grant access to admins. (Todo, modules must be enabled with the interface):

```````````````````````````````````````````
INSERT INTO `ipe`.`modulesModuleRole` (
`moduleId` ,
`roleId` ,
`useAccess` ,
`createAccess`
)
VALUES (
(select id from modulesModule where name='helloworld'), '1', '1', '1'
);

```````````````````````````````````````````


### Done!
Now refresh the Angular app and it should have the hello world icon on the start screen!
