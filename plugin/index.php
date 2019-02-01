<?php

use Payop\Config;
use Payop\Db;
use Payop\Request;
use Payop\Response;

\error_reporting(E_ALL);
\ini_set('display_errors', 1);
\ini_set('display_startup_errors', 1);

\spl_autoload_register(function($className) {
    foreach (['src', 'src/Http', 'src/Http/Requests'] as $dir) {
        $file = \sprintf(
            '%s/%s/%s',
            __DIR__,
            $dir,
            \str_replace('\\', DIRECTORY_SEPARATOR, $className).'.php'
        );

        if (\file_exists($file)) {
            include $file;
        }
    }
});

if (\file_exists(__DIR__.'/installer.php')) {
    \header('location: installer.php');
} else {
    $request = Request::createFromGlobals();
    $config = Config::create();

    $db = new Db(
        $config->get('dbHost'),
        $config->get('dbName'),
        $config->get('dbUser'),
        $config->get('dbPass'),
        $config->get('dbPort')
    );
    $logger = \Payop\Logger::create();
    $handler = new \Payop\Handler($db, $config, $logger);
    $routes = [
        'form' => 'showForm',
        'payment' => 'createPayment',
        'successful' => 'successful',
        'failed' => 'failed',
        'ipn' => 'ipn',
    ];

    $action = $request->query->get('action', 'form');
    if ($config->getInt('enableLogs')) {
        $logger->log('Request', [
            'get' => $request->query->all(),
            'post' => $request->request->all(),
            'method' => $request->getMethod(),
            'uri' => $request->getRequestUri(),
            'route' => $routes[$action] ?? '',
        ]);
    }

    if (isset($routes[$action])) {
        $response = $handler->{$routes[$action]}($request);
    } else {
        $response = $handler->pageNotFound($request);
    }

    $response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate, no-store;');
    $response->headers->set('Connection', 'keep-alive');
    $response->send();
    Response::closeOutputBuffers(2, true);

    return $response;
}
