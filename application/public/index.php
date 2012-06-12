<?php
/**
 * Created by JetBrains PhpStorm.
 * User: Vika
 * Date: 03.06.12
 * Time: 15:57
 */

error_reporting(E_ALL);
ini_set('display_errors', true);

(function_exists('date_default_timezone_set') && function_exists('date_default_timezone_get'))
    && @date_default_timezone_set(@date_default_timezone_get());


// Define path to application directory.
defined('APPLICATION_PATH')
    || define('APPLICATION_PATH', realpath(dirname(__FILE__) . '/../'));


// Include commons.
require_once APPLICATION_PATH . '/Consts.php';
require_once APPLICATION_PATH . '/Logger.php';
require_once APPLICATION_PATH . '/Router.php';
require_once APPLICATION_PATH . '/Template.php';
require_once APPLICATION_PATH . '/model/Life.php';

// Load config.
$config = parse_ini_file(APPLICATION_PATH . '/config/config.ini', true);
assert(null != $config);

// Init DB.
assert(array_key_exists('db', $config));

require_once APPLICATION_PATH . '/model/LifeDb.php';
LifeDb::init($config['db']);

// Init cache. Optional.
if (array_key_exists('memcached', $config)) {
    LifeDb::initMemcache($config['memcached']);
}

// Init logger.
Logger::init($config['log']);


try {
    // Route.
    Router::getInstance()->route();
} catch (Exception $e) {
    // Exception. Show the error page.
    Logger::err($e->getCode() . '; ' . $e->getMessage() . '; ' . $e->getTraceAsString());

    $template = Template::getInstance();
    $template->message = "An error has occurred:<pre>\n" .
                         $e->getCode() . '; ' . $e->getMessage() . '; ' . $e->getTraceAsString() .
                         '</pre>';

    $template->render('error');
}
