<?php

require_once __DIR__."/controllers/events.php";
require_once __DIR__."/controllers/notifications.php";
require_once __DIR__."/controllers/status.php";
require_once __DIR__."/controllers/user_status.php";
require_once __DIR__."/controllers/users.php";
require_once __DIR__."/controllers/commands.php";
require_once __DIR__."/controllers/cron.php";

$app->group('/events', function() use ($app) {
    $app->get('', 'EventsController::getAll');
    $app->get('/{id}', 'EventsController::getById');
    $app->post('', 'EventsController::create');
    $app->delete('/{id}', 'EventsController::delete');
});

$app->group('/notifications', function() use ($app) {
    $app->get('', 'NotificationsController::getAll');
    $app->get('/{id}', 'NotificationsController::getById');
    $app->post('', 'NotificationsController::create');
    $app->delete('/{id}', 'NotificationsController::delete');
});

$app->group('/status', function() use ($app) {
    $app->get('', 'StatusController::getAll');
    $app->get('/{id}', 'StatusController::getById');
    $app->post('', 'StatusController::create');
    $app->delete('/{id}', 'StatusController::delete');
});

$app->group('/user_status', function() use ($app) {
    $app->get('', 'UserStatusController::getAll');
    $app->get('/{id}', 'UserStatusController::getById');
    $app->post('', 'UserStatusController::create');
    $app->put('/{id}', 'UserStatusController::update');
    $app->delete('/{id}', 'UserStatusController::delete');
});

$app->group('/users', function() use ($app) {
    $app->get('', 'UsersController::getAll');
    $app->get('/{id}', 'UsersController::getById');
    $app->post('', 'UsersController::create');
    $app->delete('/{id}', 'UsersController::delete');
});

$app->group('/command' , function() use ($app) {
    $app->post('/iam', 'CommandsController::iam');
    $app->post('/whereis', 'CommandsController::whereis');
    $app->post('/notifyme', 'CommandsController::notifyme');
});

$app->group('/cron', function() use ($app) {
    $app->get('', 'CronController::runCron');
});

