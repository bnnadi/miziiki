<?php
/**
 * Created by PhpStorm.
 * User: bisikennadi
 * Date: 8/25/14
 * Time: 11:44 AM
 */

error_reporting(E_ALL);

try {

    $config = new Phalcon\Config\Adapter\Ini(__DIR__ . '/../app/config/config.ini');

    $loader = new \Phalcon\Loader();
    $loader->registerDirs(array(
        __DIR__ . $config->application->controllersDir,
        __DIR__ . $config->application->pluginsDir,
        __DIR__ . $config->application->libraryDir,
        __DIR__ . $config->application->modelsDir,
    ))->register();

    $di = new Phalcon\DI\FactoryDefault();

    $di->set('dispatcher', function() use ($di) {

        $eventsManager = $di->getShared('eventsManager');

        $security = new Security($di);

        $eventsManager->attach('dispatch', $security);

        $dispatcher = new Phalcon\Mvc\Dispatcher();
        $dispatcher->setEventsManager($eventsManager);

        return $dispatcher;
    });

    $di->set('url', function() use ($config){
        $url = new \Phalcon\Mvc\Url();
        $url->setBaseUri($config->application->baseUri);
        return $url;
    });

    $di->set('view', function() use ($config) {

        $view = new \Phalcon\Mvc\View();

        $view->setViewsDir(__DIR__ . $config->application->viewsDir);

        $view->registerEngines(array(
            ".phmtl" => 'Phalcon\Mvc\View\Engine\Volt'
        ));

        return $view;
    });

    $di->set('db', function() use ($config) {
        return new \Phalcon\Db\Adapter\Pdo\Mysql(array(
            "host" => $config->database->host,
            "username" => $config->database->username,
            "password" => $config->database->password,
            "dbname" => $config->database->name
        ));
    });

    $di->set('session', function() {
        $session = new Phalcon\Session\Adapter\Files();
        $session->start();
        return $session;
    });

    $di->set('flash', function(){
        return new Phalcon\Flash\Direct(array(
            'error' => 'alert-box alert',
            'success' => 'alert-box success',
            'notice' => 'alert-box info',
        ));
    });

    $di->set('elements', function(){
        return new Elements();
    });

    $application = new \Phalcon\Mvc\Application();
    $application->setDI($di);
    echo $application->handle()->getContent();


} catch(\Phalcon\Exception $e) {
    echo "PhalconException: ", $e->getMessage();
} catch (PDOException $e){
    echo $e->getMessage();
}