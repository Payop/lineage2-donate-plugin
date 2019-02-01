<?php

use Payop\Installer\Installer;
use Payop\Request;
use Payop\Response;

\error_reporting(E_ALL);
\ini_set('display_errors', 1);
\ini_set('display_startup_errors', 1);

\spl_autoload_register(function($className) {
    $file = \sprintf(
        '%s/src/%s',
        __DIR__,
        \str_replace('\\', DIRECTORY_SEPARATOR, $className).'.php'
    );
    if (\file_exists($file)) {
        include $file;
    }
});

$request = Request::createFromGlobals();
$installer = Installer::create();

$step = $request->query->get('step');
if ($step && $installer->hasStep($step)) {
    $content = $installer->runStep($step, $request);
} else {
    $content = $installer->run($request);
}

$response = Response::create($content);
$response->headers->set('Cache-Control', 'private, max-age=0, must-revalidate, no-store;');
$response->headers->set('Connection', 'keep-alive');
$response->send();
Response::closeOutputBuffers(2, true);

return $response;