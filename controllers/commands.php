<?php

class CommandsController {

    public static function iam($request, $response, $args) {

        $body = $request->getParsedBody();
        $user = Users::getByName($body['user_name']);
        $user_status = UserStatus::getById(intval($user['id']));

        $words = explode(' ', $body['text']);
        if(sizeof($words) == 0)
            throw new Exception("You have not provided any parameters");
        $firstWord = strtolower($words[0]);

        $status = Status::getByName($firstWord)[0];

        if(!$status)
            throw new Exception("There are no statuses with that name");
        $params = [
            "user_id" => $user['id'],
            "status_id" => intval($status['id']),
            "default_status_id" => intval($status['id'])
        ];

        if(!$user_status) {
            $updated_user_status = UserStatus::create($params);
        } else {
            $updated_user_status = UserStatus::update(intval($user['id']), $params);
        }

        $message = ["text" => "Your status has been set to ".$status['prefix']."`".$firstWord."`!"];


        if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w")
            return $response->withJson($message);
        return $response->withJson($updated_user_status);
    }

    public static function notifyme($request, $response, $args) {
        $body = $request->getParsedBody();
    }

    public static function whereis($request, $response, $args) {
        $body = $request->getParsedBody();

        $words = explode(" ", $body['text']);
        if(sizeof($words) != 1) {
            if (isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
                $message = ["text" => "I didn't quite understand that! /whereis should only have *1* parameter (/whereis [name])."];
                return $response->withJson($message);
            }
            throw new Exception("You may only have 1 parameter for the whereis command");
        }

        $firstWord = strtolower($words[0]);

        $from_user = Users::getByName($firstWord);
        if(!$from_user) {
            if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
                $message = ["text" => "Oops! It looks like there are no users with the slack handle \"".$firstWord."\"!"];
                return $response->withJson($message);
            }
            throw new Exception("There is no user with the slack handle ".$firstWord);
        }

        $from_user_status = UserStatus::getById($from_user['id']);
        if(!$from_user_status) {
            if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
                $message = ["text" => "Sorry, but ".$firstWord." has not set up a status yet. (Maybe you should tell them how cool StatusBot is!)"];
                return $response->withJson($message);
            }
            throw new Exception($firstWord." has not set up a status yet.");
        }

        $status = Status::getById($from_user_status['status_id']);

        if(isset($body['token']) && $body['token'] == "tABWNlxemplvZ2YtVeEMEB5w") {
            $message = ["text" => "@".$firstWord." is currently ".$status['prefix']."`".$status['status']."`!"];
            return $response->withJson($message);
        }
        return $response->withJson($from_user_status);
    }

}