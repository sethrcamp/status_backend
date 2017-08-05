<?php

require_once __DIR__."/../models/notifications.php";

class NotificationsController {
    public static function getAll ($request, $response, $args) {
        $notifications = Notifications::getAll();
        return $response->withJson($notifications);
    }
    public static function getById ($request, $response, $args) {
        $id = $args['id'];
        $notification = Notifications::getById($id);
        if(!$notification)
            throw new Exception("There is no notification with this id.");
        return $response->withJson($notification);
    }
    public static function create ($request, $response, $args) {
        $body = $request->getParsedBody();
        if(!isset($body['to_user_id']) ||
            !isset($body['from_user_id']) ||
            !isset($body['start_time']) ||
            !isset($body['both_users_free']))
            throw new Exception("Missing required parameter");

        $notification = Notifications::create($body);
        return $response->withJson($notification);
    }

    public static function delete ($request, $response, $args) {
        $id = $args['id'];
        $notification = Notifications::delete($id);
        return $response->withJson($notification);

    }
}