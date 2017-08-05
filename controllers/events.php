<?php

require_once __DIR__."/../models/events.php";

class EventsController {
    public static function getAll ($request, $response, $args) {
        $events = Events::getAll();
        return $response->withJson($events);
    }
    public static function getById ($request, $response, $args) {
        $id = $args['id'];
        $event = Events::getById($id);
        if(!$event)
            throw new Exception("There is no event with this id.");
        return $response->withJson($event);
    }
    public static function create ($request, $response, $args) {
        $body = $request->getParsedBody();
        if(!isset($body['user_id']) ||
            !isset($body['status_id']) ||
            !isset($body['start_time']) ||
            !isset($body['description']))
            throw new Exception("Missing required parameter");

        $event = Events::create($body);
        return $response->withJson($event);
    }

    public static function delete ($request, $response, $args) {
        $id = $args['id'];
        $event = Events::delete($id);
        return $response->withJson($event);

    }
}