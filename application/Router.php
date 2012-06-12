<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vika
 * Date: 04.06.12
 * Time: 3:58
 */

class Router
{
    /**
     * @var Router
     */
    private static $_instance;

    public static function getInstance()
    {
		if (null == self::$_instance) {
            self::$_instance = new self();
		}

        return self::$_instance;
    }

    public function route()
    {
        // Start session.
        session_start();

        // Evaluate route.
        $route = !empty($_GET['route']) ? $_GET['route'] : '';

        $route = trim($route, '/');
        $route = strtolower($route);

        if (!$route) {
            // No route passed. Use the default one.
            $route = 'life';
        } else {
            // Route passed.

            // Validate that private scripts are not requested.
            $parts = explode('/', $route);

            foreach ($parts as $part) {
                if (substr($part, 0, 1) == '_') {
                    throw new Exception('Unauthorized request.');
                }
            }

            // Validate that such file exists.
            if (!file_exists(APPLICATION_PATH . "/controller/$route.php")) {
                throw new Exception('Unauthorized request.');
            }
        }

        include APPLICATION_PATH . "/controller/$route.php";
    }

}
