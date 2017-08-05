<?php

class CronController {
    public static function runCron($request, $response, $args) {
        return $response->withJson(["error"=> "no"]);
    }
}