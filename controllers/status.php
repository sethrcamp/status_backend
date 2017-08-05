<?php

require_once __DIR__."/../models/status.php";

class StatusController {
    public static function getAll ($request, $response, $args) {
        $statuses = Status::getAll();
        return $response->withJson($statuses);
    }
    public static function getById ($request, $response, $args) {
        $id = $args['id'];
        $status = Status::getById($id);
        if(!$status)
            throw new Exception("There is no status with this id.");
        return $response->withJson($status);
    }
    public static function create ($request, $response, $args) {
        $body = $request->getParsedBody();
        if(!isset($body['status']))
            throw new Exception("Missing required parameter");

        $status = Status::create($body);
        return $response->withJson($status);
    }

    public static function delete ($request, $response, $args) {
        $id = $args['id'];
        $status = Status::delete($id);
        return $response->withJson($status);

    }
}