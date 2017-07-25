<?php
require_once 'vendor/autoload.php';

use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\ResponseInterface;
use Slim\Slim;

date_default_timezone_set("America/New_York");

require 'config/config.php';

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

$c = new \Slim\Container();
$c['errorHandler'] = function ($c) {
    return function ($request, $response, $exception) use ($c) {
        global $app;
        $data = [
            'status' => $exception->getCode(),
            'error' => true,
            'msg' => $exception->getMessage()
        ];
        return $c['response']->withStatus($exception->getCode() != 0 ? $exception->getCode(): 500)
            ->withHeader('Content-Type', 'application/json')
            ->write(json_encode($data));
    };
};

$c['mode'] = 'development';

$app = new \Slim\App($c);

$container = $app->getContainer();

require 'config/db.php';
r

$app->add(function (ServerRequestInterface $request, ResponseInterface $response, callable $next) {
    // Use the PSR 7 $request object

    return $next($request, $response);
});

$app->add(function($request, $response, $next) {
    $response = $response->withHeader('Content-Type', 'application/json');
    return $next($request, $response);
});


$app->group('', function() use ($app){


});



$app->run();