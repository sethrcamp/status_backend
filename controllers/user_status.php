<?php

require_once __DIR__."/../models/user_status.php";

class UserStatusController {
    public static function getAll ($request, $response, $args) {
        $statuses = UserStatus::getAll();
        return $response->withJson($statuses);
    }
    public static function getById ($request, $response, $args) {
        $id = $args['id'];
        $status = UserStatus::getById($id);
        if(!$status)
            throw new Exception("There is no user status with this id.");
        return $response->withJson($status);
    }
    public static function create ($request, $response, $args) {
        $body = $request->getParsedBody();
        if(!isset($body['status_id']) || !isset($body['default_status_id']))
            throw new Exception("Missing required parameter");

        $status = UserStatus::create($body);
        return $response->withJson($status);
    }
    public static function update ($request, $response, $args) {
        $id = $args['id'];
        $status = UserStatus::getById($id);
        if(!$status)
            throw new Exception("There is no user status with this id.");
        $body = $request->getParsedBody();
        $updated_status = Status::update($id, $body);
        return $response->withJson($updated_status);
    }
    public static function delete ($request, $response, $args) {
        $id = $args['id'];
        $status = UserStatus::delete($id);
        return $response->withJson($status);

    }
}