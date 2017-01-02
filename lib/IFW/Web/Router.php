<?php
namespace IFW\Web;

use IFW;
use IFW\Exception\HttpException;


/**
 * The router routes requests to their controller actions
 *
 * Some core routes are created in this class in the private function "_collectRoutes".
 * Each module can add routes too in their module file. {@see \IFW\AbstractModule}.
 * 
 * `````````````````````````````````````````````````````````````````````````````
 * <?php
 * namespace GO\Modules\Bands;
 * 
 * use IFW\AbstractModule;
 * use GO\Modules\Bands\Controller\BandController;
 * use GO\Modules\Bands\Controller\HelloController;
 * 
 * class BandsModule extends AbstractModule {
 * 	
 * 	const PERMISSION_CREATE = 1;
 * 	
 * 	public function routes() {
 * 		BandController::routes()
 * 				->get('bands', 'store')
 * 				->get('bands/0','new')
 * 				->get('bands/:bandId','read')
 * 				->put('bands/:bandId', 'update')
 * 				->post('bands', 'create')
 * 				->delete('bands/:bandId','delete');
 * 		
 * 		HelloController::routes()
 * 				->get('bands/hello', 'name');
 * 	}
 * }
 * `````````````````````````````````````````````````````````````````````````````
 * 
 * 
 * == Catch all route:
 * 
 * Using the stop parameter of addRoute you can define a catch all.
 * Using a '*' as prefix to a parameter it will include the whole path after it.
 * 
 * So when calling /api/devtools/test/bar/foo/foobar the following route will
 * call the ation with: $path = 'bar/foo/foobar'; and $bar = 'foobar'; :
 *
 * `````````````````````````````````````````````````````````````````````````````
 * 
 * ->addRoute('*', 'devtools/test/*path/foo/:bar', 'test', true);
 * 
 * `````````````````````````````````````````````````````````````````````````````
 * 
 * {@see \IFW\Controller}
 * 
 * 
 * @copyright (c) 2014, Intermesh BV http://www.intermesh.nl
 * @author Merijn Schering <mschering@intermesh.nl>
 * @license http://www.gnu.org/licenses/agpl-3.0.html AGPLv3
 */

class Router extends IFW\Router {	
	
	/**
	 *
	 * @var RouteCollection[] 
	 */
	private $routeCollections = [];
	
	/**
	 * The current route. 
	 * 
	 * eg. /contacts/1
	 * 
	 * @var string 
	 */
	private $route;	

	/**
	 * Get the params passed in a route.
	 * 
	 * eg. /contacts/1 where 1 is a "contactId" would return ["contactId" => 1];
	 * 
	 * @var array
	 */
	private $routeParams = [];
	
	
	private $routeParts;
	
	/**
	 * When building the route table this is set to the module currently processed
	 * so that we can record this.
	 * 
	 * @var string 
	 */
	private $currentModuleName;
	
	/**
	 * Add a {@see \IFW\Controller} to the router
	 * 
	 * This function returns a RouteCollection object so you can add 
	 * route mappings to controller methods.
	 * 
	 * <code>
	 * 
	 * $router->addController(AuthController::class)				
	 *			->get('auth', 'isLoggedIn')
	 *			->post('auth', 'login')
	 *			->delete('auth', 'logout')						
	 *			->post('auth/users/:userId/switch-to', 'switchTo');
	 * 
	 * </code>
	 * 
	 * @param $controllerName
	 * @return RouteCollection 
	 */
	public function addRoutesFor($controllerName) {
		$routesCollection = new RouteCollection($controllerName, $this->currentModuleName);
		$this->routeCollections[] = $routesCollection;
		
		return $routesCollection;
	}
	
	private $optimizedRoutes = [];
	
	
	/**
	 * Converts route definitions to an optimized array for the router
	 * 
	 * <code>
	 *array(18) {
  ["auth"]=>
  array(2) {
    ["methods"]=>
    array(4) {
      ["GET"]=>
      array(2) {
        ["routeParams"]=>
        array(0) {
        }
        ["actions"]=>
        array(1) {
          [0]=>
          array(2) {
            [0]=>
            string(46) "GO\Core\Auth\Browser\Controller\AuthController"
            [1]=>
            string(10) "isLoggedIn"
          }
        }
      }
      ["POST"]=>
      array(2) {
        ["routeParams"]=>
        array(0) {
        }
        ["actions"]=>
        array(1) {
          [0]=>
          array(2) {
            [0]=>
            string(46) "GO\Core\Auth\Browser\Controller\AuthController"
            [1]=>
            string(5) "login"
          }
        }
      }
      ["DELETE"]=>
      array(2) {
        ["routeParams"]=>
        array(0) {
        }
        ["actions"]=>
        array(1) {
          [0]=>
          array(2) {
            [0]=>
            string(46) "GO\Core\Auth\Browser\Controller\AuthController"
            [1]=>
            string(6) "logout"
          }
        }
      }
      ["PUT"]=>
      array(2) {
        ["routeParams"]=>
        array(0) {
        }
        ["actions"]=>
        array(0) {
        }
      }
    }
    ["children"]=>
    array(2) {
      ["users"]=>
      array(2) {
        ["methods"]=>
        array(4) {
          ["POST"]=>
          array(2) {
            ["routeParams"]=>
            array(1) {
              [0]=>
              string(6) "userId"
            }
            ["actions"]=>
            array(1) {
              [0]=>
              array(2) {
                [0]=>
                string(38) "GO\Core\Modules\Users\Controller\UserController"
                [1]=>
                string(6) "create"
              }
            }
          }
          ["GET"]=>
          array(2) {
            ["routeParams"]=>
            array(1) {
              [0]=>
              string(6) "userId"
            }
            ["actions"]=>
            array(2) {
              [0]=>
              array(2) {
                [0]=>
                string(38) "GO\Core\Modules\Users\Controller\UserController"
                [1]=>
                string(5) "store"
              }
              [1]=>
              array(2) {
                [0]=>
                string(38) "GO\Core\Modules\Users\Controller\UserController"
                [1]=>
                string(4) "read"
              }
            }
          }
          ["PUT"]=>
          array(2) {
            ["routeParams"]=>
            array(1) {
              [0]=>
              string(6) "userId"
            }
            ["actions"]=>
            array(1) {
              [1]=>
              array(2) {
                [0]=>
                string(38) "GO\Core\Modules\Users\Controller\UserController"
                [1]=>
                string(6) "update"
              }
            }
          }
          ["DELETE"]=>
          array(2) {
            ["routeParams"]=>
            array(0) {
            }
            ["actions"]=>
            array(1) {
              [0]=>
              array(2) {
                [0]=>
                string(38) "GO\Core\Modules\Users\Controller\UserController"
                [1]=>
                string(6) "delete"
              }
            }
          }
        }
        ["children"]=>
        array(3) {
          ["switch-to"]=>
          array(2) {
            ["methods"]=>
            array(1) {
              ["POST"]=>
              array(2) {
                ["routeParams"]=>
                array(0) {
                }
                ["actions"]=>
                array(1) {
                  [1]=>
                  array(2) {
                    [0]=>
                    string(46) "GO\Core\Auth\Browser\Controller\AuthController"
                    [1]=>
                    string(8) "switchTo"
                  }
                }
              }
            }
            ["children"]=>
            array(0) {
            }
          }
          [0]=>
          array(2) {
            ["methods"]=>
            array(1) {
              ["GET"]=>
              array(2) {
                ["routeParams"]=>
                array(0) {
                }
                ["actions"]=>
                array(1) {
                  [0]=>
                  array(2) {
                    [0]=>
                    string(38) "GO\Core\Modules\Users\Controller\UserController"
                    [1]=>
                    string(3) "new"
                  }
                }
              }
            }
            ["children"]=>
            array(0) {
            }
          }
          ["filters"]=>
          array(2) {
            ["methods"]=>
            array(1) {
              ["GET"]=>
              array(2) {
                ["routeParams"]=>
                array(0) {
                }
                ["actions"]=>
                array(1) {
                  [0]=>
                  array(2) {
                    [0]=>
                    string(38) "GO\Core\Modules\Users\Controller\UserController"
                    [1]=>
                    string(7) "filters"
                  }
                }
              }
            }
            ["children"]=>
            array(0) {
            }
          }
        }
      }
      ["groups"]=>
      array(2) {
        ["methods"]=>
        array(4) {
          ["GET"]=>
          array(2) {
            ["routeParams"]=>
            array(1) {
              [0]=>
              string(7) "groupId"
            }
            ["actions"]=>
            array(2) {
              [0]=>
              array(2) {
                [0]=>
                string(39) "GO\Core\Modules\Users\Controller\GroupController"
                [1]=>
                string(5) "store"
              }
              [1]=>
              array(2) {
                [0]=>
                string(39) "GO\Core\Modules\Users\Controller\GroupController"
                [1]=>
                string(4) "read"
              }
            }
          }
          ["PUT"]=>
          array(2) {
            ["routeParams"]=>
            array(1) {
              [0]=>
              string(7) "groupId"
            }
            ["actions"]=>
            array(1) {
              [1]=>
              array(2) {
                [0]=>
                string(39) "GO\Core\Modules\Users\Controller\GroupController"
                [1]=>
                string(6) "update"
              }
            }
          }
          ["POST"]=>
          array(2) {
            ["routeParams"]=>
            array(0) {
            }
            ["actions"]=>
            array(1) {
              [0]=>
              array(2) {
                [0]=>
                string(39) "GO\Core\Modules\Users\Controller\GroupController"
                [1]=>
                string(6) "create"
              }
            }
          }
          ["DELETE"]=>
          array(2) {
            ["routeParams"]=>
            array(0) {
            }
            ["actions"]=>
            array(1) {
              [0]=>
              array(2) {
                [0]=>
                string(39) "GO\Core\Modules\Users\Controller\GroupController"
                [1]=>
                string(6) "delete"
              }
            }
          }
        }
        ["children"]=>
        array(1) {
          [0]=>
          array(2) {
            ["methods"]=>
            array(1) {
              ["GET"]=>
              array(2) {
                ["routeParams"]=>
                array(0) {
                }
                ["actions"]=>
                array(1) {
                  [0]=>
                  array(2) {
                    [0]=>
                    string(39) "GO\Core\Modules\Users\Controller\GroupController"
                    [1]=>
                    string(3) "new"
                  }
                }
              }
            }
            ["children"]=>
            array(0) {
            }
          }
        }
      }
    }
  }
	 * </code>
	 * 
	 * @return array
	 */
	private function optimizeRoutes(){		
		foreach($this->routeCollections as $routeCollection){
			$controller = $routeCollection->getControllerName();
			$moduleName = $routeCollection->getModuleName();
			
			$routes = $routeCollection->getRoutes();
			
			foreach($routes as $route){
				$method = $route[0];
				$action = $route[2];				
				$routeParts = explode('/', $route[1]);
				$stop = $route[3];
				
				$this->addRouteParts($routeParts, $method, $action, $controller, $moduleName, $stop);
			}
		}
		
		return $this->optimizedRoutes;
	}
	
	/**
	 * Get all the route collections
	 * 
	 * Each controller has a route collection
	 * 
	 * @return RouteCollection[]
	 */
	public function getRouteCollections(){
		return $this->routeCollections;
	}
	
	private function isParamPart($part) {
		$firstChar = substr($part, 0, 1);
		
		return $firstChar == ':' || $firstChar == '*';
	}
	
	private function addRouteParts($routeParts, $method, $action, $controller, $moduleName, $stop){
//		if(!isset($this->_optimizedRoutes[$method])){
//			$this->_optimizedRoutes[$method] = [];
//		}
		
		$cur = [
				'children' => &$this->optimizedRoutes
		];
		
		//eg /bands/:bandId/albums/:albumId
		//At the bands we store bandId as routeParams and at albums we store albumId.
		//But the total of route params is 2 for this route.		
		$totalRouteParams = 0; 
		
		foreach($routeParts as $part) {
			
//			if(empty($part)) {
//				var_dump($routeParts);
//				throw new \Exception();
//			}
			
			if(!$this->isParamPart($part)){			
				if(!isset($cur['children'][$part])) {
					$cur['children'][$part] = [
						'methods' => [],
						'children' => []
					];
				}
				
				if(!isset($cur['children'][$part]['methods'][$method])) {
					$cur['children'][$part]['methods'][$method] = [
						'stop' => false,
						'routeParams' => [],
						'actions' => [], //indexed with no. of params
					];
				}

				$cur = &$cur['children'][$part];
			}else
			{
				//route parameter
//				$routeParam = substr($part, 1);
				if(!in_array($part, $cur['methods'][$method]['routeParams'])){
					$cur['methods'][$method]['routeParams'][] = $part;
				}
				
				$totalRouteParams++;
			}
		}	
		$cur['methods'][$method]['stop'] = $stop;
		$cur['methods'][$method]['actions'][$totalRouteParams] = [$controller, $action, $moduleName];		
	}
	
	public function getInterfaceType() {
		return 'Web';
	}
	
	
	/**
	 * Called on application initialization
	 */
	public function initRoutes() {
		$this->optimizedRoutes = IFW::app()->getCache()->get('routes');
		if(!$this->optimizedRoutes) {	
			
			IFW::app()->debug("Initializing routes");
			
			foreach(IFW::app()->getModules() as $moduleName) {				
				$this->currentModuleName = $moduleName;				
				$moduleName::defineWebRoutes($this);
			}
			
			$this->optimizeRoutes();
			IFW::app()->getCache()->set('routes', $this->optimizedRoutes);
		}
	}	
	
	/**
	 * The current route. 
	 * 
	 * eg. /contacts/1
	 * 
	 * @param string 
	 */
	public function getRoute() {
		return $this->route;
	}

	/**
	 * Finds the controller that matches the route and runs it.
	 */
	public function run() {
		
		$this->route = IFW::app()->getRequest()->getRoute();
		
		if(empty($this->route)){
			IFW::app()->getResponse()->redirect($this->buildUrl('system/check'));
		}

		$this->routeParts = explode('/', $this->route);
		$this->walkRoute($this->optimizedRoutes);

	}	
	
	/**
	 * Get the params passed in a route.
	 * 
	 * eg. /contacts/1 where 1 is a "contactId" would return ["contactId" => 1];
	 * 
	 * @return array ["paramName" => "value"]
	 */
	public function getRouteParams() {
		return $this->routeParams;
	}	
	
	/**
	 * 
	 * @param string[] $routes The available routes in the router
	 * @throws HttpException
	 */
	private function walkRoute($routes) {

		$routePart = array_shift($this->routeParts);
		
		if(!isset($routes[$routePart])){
			throw new \IFW\Exception\HttpException(404, "Route '$routePart' not found");
		}
		$config = $routes[$routePart];
		
		/* eg:
		 * array(2) {
    ["methods"]=>
    array(4) {
      ["GET"]=>
      array(2) {
        ["routeParams"]=>
        array(0) {
        }
        ["actions"]=>
        array(1) {
          [0]=>
          array(2) {
            [0]=>
            string(46) "GO\Core\Auth\Browser\Controller\AuthController"
            [1]=>
            string(10) "isLoggedIn"
          }
        }
      }
		 */
		
		$method = \IFW::app()->getRequest()->method;
		
		$found = $this->extractRouteParams($config, $method);
		if(!$found) {
			$method = '*';
			$found = $this->extractRouteParams($config, $method);
		}
		
		if(!$found) {
			throw new \IFW\Exception\HttpException(405, "HTTP method '".\IFW::app()->getRequest()->method."' not allowed. Only ".implode(',', array_keys($config['methods']))." are supported");
		}

		if (!empty($this->routeParts) && !$config['methods'][$method]['stop']) {
			return $this->walkRoute($config['children']);
		} else {			
			$action = $this->getAction($config, $method);
			
			//send router parameters and GET parameters together
			$params = array_merge($this->routeParams, $_GET);

			$controller = new $action[0]();
			$controller->run(strtolower($action[1]), $params);
		}
		
	}
	/**
	 * The fill find the controller and controller action method based on the number
	 * of route parameters and HTTP request method (PUT, POST, GET etc.)
	 * 
	 * @param type $config
	 * @param type $method
	 * @return type
	 * @throws \IFW\Exception\HttpException
	 */
	private function getAction($config, $method) {
		$paramCount = count($this->routeParams);
			
		if (empty($config['methods'][$method]['actions'][$paramCount])) {
//				var_dump($config['methods']['GET']);
//				throw new HttpException(405, "HTTP ".$method." not allowed. Only ".implode(',', array_keys($config['methods']))." are supported");
//				throw new Exception("No action defined for this route!");
			throw new \IFW\Exception\HttpException(405, "No action defined for ".$method." on route ".$this->route." params: ".var_export($this->routeParams, true));
		}
		
		return $config['methods'][$method]['actions'][$paramCount];
	}

	/**
	 * Extracts route parameters
	 * For example in /contact/:contactId ":contactId" is a route parameter.
	 * 
	 * So when /contact/1 is requested $this->routeParams['contactId']=1; will be set by this function.
	 * 
	 * The part is shifted off the route parts array.
	 * 
	 * @param array $config
	 * @param string $method
	 * @return boolean false if there is no configuration for the given HTTP method.
	 */
	private function extractRouteParams($config, $method) {
		
		//HTTP method has no routes
		if(!isset($config['methods'][$method])) {
			return false;
		}
		
		foreach ($config['methods'][$method]['routeParams'] as $paramTypeAndName) {
			
			if(empty($this->routeParts)){
				break;
			}
			
			$part = array_shift($this->routeParts);			
			if(isset($config['children'][$part])){
			
				//put back the part
				array_unshift($this->routeParts, $part);
				
				// If there's an exact child match.
				// Like 0 here matches both rules. In this case the rule with the exact match wins.
				//
				// Rules example:
				// ->get('auth/users/0','new')
				// ->get('auth/users/:userId','read')
				
				break;
			}
			
			
			//: or * Star should take all of the route
			$paramType = substr($paramTypeAndName, 0,1);
			$paramName = substr($paramTypeAndName,1);
			
			$this->routeParams[$paramName] = $part;
			if($paramType=='*' && !empty($this->routeParts)) {
				
				$this->routeParams[$paramName] .= '/'.implode('/', $this->routeParts);
			}
		}
		
		return true;
	}

	/**
	 * Get the name of the module the router directed us to.
	 * 
	 * @param string eg. 
	 */
	public function getModuleName() {
		return $this->currentModuleName;
	}

}
