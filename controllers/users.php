<?php

require_once __DIR__."/../models/users.php";

class UsersController {
    public static function getAll ($request, $response, $args) {

        $statuses = Users::getAll();
        die("hey there");
        return $response->withJson($statuses);
    }
    public static function getById ($request, $response, $args) {
        $id = $args['id'];
        $status = Users::getById($id);
        if(!$status)
            throw new Exception("There is no user with this id.");
        return $response->withJson($status);
    }
    public static function create ($request, $response, $args) {
        $body = $request->getParsedBody();
        if(!isset($body['slack_handle']))
            throw new Exception("Missing required parameter");

        $status = Users::create($body);
        return $response->withJson($status);
    }
    public static function update ($request, $response, $args) {
        $id = $args['id'];
        $status = Users::getById($id);
        if(!$status)
            throw new Exception("There are no users with this id.");
        $body = $request->getParsedBody();
        $updated_status = Users::update($id, $body);
        return $response->withJson($updated_status);
    }
    public static function delete ($request, $response, $args) {
        $id = $args['id'];
        $status = Users::delete($id);
        return $response->withJson($status);

    }
}